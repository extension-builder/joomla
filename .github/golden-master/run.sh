#!/usr/bin/env bash
#
# Compile one component twice — once with the released compiler, once with this
# working tree — and report what changed in the component that came out.
#
# The released compiler comes from the octoleo/joomengine image. Its entrypoint
# installs Joomla, installs the released JCB package, and then runs whatever
# JOOMLA_CLI_COMMANDS holds, which is the first compile. That takes a while, and
# the entrypoint says so in the container log when each step is done, so this
# script waits for those lines rather than guessing.
#
# This working tree then goes in the way JCB expects: zipped, handed to the
# container, and installed with the same extension:install the entrypoint uses.
# JCB installs itself — ComponentbuilderInstallerScript::moveFolders() copies
# every folder in the package that is not media, admin or site into the site
# root, which is how libraries/vendor_jcb is deployed.
#
# usage: .github/golden-master/run.sh [output-directory]
#
# Environment:
#   COMPONENT       GUID of the component to compile (default: the demo one)
#   COMPILE_EXTRA   Extra options for both compiles (see the default below)
#   KEEP_STACK      Leave the containers running afterwards when set to 1
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/.github/golden-master/docker-compose.yml"
OUT_DIR="${1:-${REPO_ROOT}/.golden-master}"

COMPONENT="${COMPONENT:-1c20aec5-bf1a-44e7-9deb-d1c920ca591d}"
KEEP_STACK="${KEEP_STACK:-0}"

# This work targets Joomla 6, and only Joomla 6. It is not a knob: a run that
# built for anything else would be comparing output nobody here cares about, so
# the target is fixed and every package that comes out is checked against it.
JOOMLA_VERSION=6

WEBROOT=/var/www/html
INSTALL_TIMEOUT=900

# Both compiles must be given the same options, and two of them matter.
#
#   debug-line-nr  writes the class and line that emitted each generated line
#                  into the output. Moving a method to another class changes
#                  every one of those markers, which would bury the real diff.
#   build-date     is stamped into what is generated, so it must not be "now",
#                  or the two runs differ for no reason worth reading.
COMPILE_EXTRA="${COMPILE_EXTRA:---debug-line-nr=0 --add-build-date=2 --build-date=2026-01-01}"

COMPILE_EXTRA="--joomla-version=${JOOMLA_VERSION} ${COMPILE_EXTRA}"

# The command the container runs for us, and that we run again ourselves.
COMPILE_COMMAND="componentbuilder:compile:component --component=${COMPONENT} ${COMPILE_EXTRA}"
export JCB_COMPILE_COMMAND="${COMPILE_COMMAND}"

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }

compose() { docker compose -f "${COMPOSE_FILE}" "$@"; }

cleanup() {
	if [[ "${KEEP_STACK}" != "1" ]]
	then
		say "Removing the stack"
		compose down -v >/dev/null 2>&1 || true
	fi
}
trap cleanup EXIT

mkdir -p "${OUT_DIR}"
rm -rf "${OUT_DIR:?}/"*

# Wait for a line the entrypoint writes when it finishes a step.
#
# $1  what to wait for, as a fixed string
# $2  what to call it in the log
wait_for_log() {
	local marker="$1" what="$2"
	local deadline=$(( SECONDS + INSTALL_TIMEOUT ))

	say "Waiting for the container to report ${what}"

	while true
	do
		if compose logs joomla 2>/dev/null | grep -qF "${marker}"
		then
			return 0
		fi

		# The entrypoint says so plainly when a CLI command of its own fails.
		if compose logs joomla 2>/dev/null | grep -qF 'Joomla CLI command failed:'
		then
			say "The container reported a failed CLI command. Its log:"
			compose logs joomla | tail -60
			exit 1
		fi

		if (( SECONDS > deadline ))
		then
			say "Gave up after ${INSTALL_TIMEOUT}s waiting for ${what}. Container log:"
			compose logs joomla | tail -80
			exit 1
		fi

		sleep 5
	done
}

# Bring out everything the compiler just wrote.
#
# One compile can leave several packages behind - the component itself, and a
# package for each module and plugin it builds. Taking only the newest compares
# one of them and calls it the component, so take the lot.
#
# $1  a name for this run
take_packages() {
	local name="$1" dest="${OUT_DIR}/${name}" package
	local -a packages=()

	mkdir -p "${dest}"

	while IFS= read -r package
	do
		package="${package%$'\r'}"

		if [[ -n "${package}" ]]
		then
			packages+=("${package}")
		fi
	done < <(compose exec -T joomla sh -c "ls -1 ${WEBROOT}/tmp/*.zip 2>/dev/null")

	if [[ ${#packages[@]} -eq 0 ]]
	then
		say "The ${name} compile left no package in ${WEBROOT}/tmp. The container log:"
		compose logs joomla | tail -60
		exit 1
	fi

	say "The ${name} compiler wrote ${#packages[@]} package(s)"

	for package in "${packages[@]}"
	do
		printf '    %s\n' "${package}"
		compose cp "joomla:${package}" "${dest}/$(basename "${package}")"
	done

	assert_target "${name}" "${packages[@]}"

	compose exec -T joomla sh -c "rm -f ${WEBROOT}/tmp/*.zip"
}

# Refuse to compare anything that was not built for the target we asked for.
#
# JCB writes the target into the package name as a __J<n> suffix. Reading it
# back is the only statement about the target that comes from the compiler
# rather than from the flag we passed it. A package with no such suffix says
# nothing either way and is left alone.
#
# $1   a name for this run
# $2+  the packages it wrote
assert_target() {
	local name="$1"
	shift

	local package base wrong=0

	for package in "$@"
	do
		base="$(basename "${package}" .zip)"

		if [[ "${base}" =~ __J([0-9]+)$ ]] && [[ "${BASH_REMATCH[1]}" != "${JOOMLA_VERSION}" ]]
		then
			printf '    built for Joomla %s, not %s: %s\n' \
				"${BASH_REMATCH[1]}" "${JOOMLA_VERSION}" "${base}"
			wrong=1
		fi
	done

	if (( wrong ))
	then
		say "The ${name} compile did not build for Joomla ${JOOMLA_VERSION}"
		exit 1
	fi
}

# Lay every package of a run out side by side, each under its own name.
#
# $1  the directory holding that run's packages
# $2  where to unpack them
unpack_packages() {
	local from="$1" into="$2" package

	for package in "${from}"/*.zip
	do
		[[ -e "${package}" ]] || continue

		mkdir -p "${into}/$(basename "${package}" .zip)"
		unzip -q "${package}" -d "${into}/$(basename "${package}" .zip)"
	done
}

say "Starting Joomla, which installs the released JCB and compiles with it"
say "Compile command: ${COMPILE_COMMAND}"
compose up -d

# The entrypoint installs the released JCB package first...
wait_for_log \
	'Joomla CLI command succeeded: extension:install --path /usr/src/joomengine/jcb.zip' \
	'the released JCB is installed'

# ...and only then runs the compile we asked it for.
wait_for_log \
	"Joomla CLI command succeeded: componentbuilder:compile:component --component=${COMPONENT}" \
	'the first compile is done'

compose logs joomla > "${OUT_DIR}/baseline.log" 2>&1
take_packages baseline

say "Packaging this working tree"
PACKAGE="${OUT_DIR}/jcb-under-test.zip"
(
	cd "${REPO_ROOT}"
	# The test suite carries its own composer vendor tree, which is enormous and
	# is no part of what JCB installs.
	zip -qr "${PACKAGE}" . \
		-x '.git/*' '.github/*' '.golden-master/*' 'libraries/vendor_jcb/tests/*'
)
say "Packaged $(du -h "${PACKAGE}" | cut -f1)"

say "Installing it the way JCB is installed"
compose cp "${PACKAGE}" "joomla:/tmp/jcb-under-test.zip"

if ! compose exec -T joomla php "${WEBROOT}/cli/joomla.php" \
	extension:install --path /tmp/jcb-under-test.zip --no-interaction \
	> "${OUT_DIR}/install.log" 2>&1
then
	say "Installing this working tree failed. Its output:"
	cat "${OUT_DIR}/install.log"
	exit 1
fi

cat "${OUT_DIR}/install.log"

# Prove the install replaced the compiler, rather than trusting that it did.
# If these match, the container is still running the released compiler and the
# comparison below would be the release against itself.
LOCAL_SUM="$(md5sum "${REPO_ROOT}/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Helper/Interpretation.php" | cut -d' ' -f1)"
CONTAINER_SUM="$(compose exec -T joomla md5sum \
	"${WEBROOT}/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Helper/Interpretation.php" \
	| cut -d' ' -f1 | tr -d '\r')"

if [[ "${LOCAL_SUM}" != "${CONTAINER_SUM}" ]]
then
	say "The install did not replace the compiler"
	printf '  working tree: %s\n  container:    %s\n' "${LOCAL_SUM}" "${CONTAINER_SUM}"
	exit 1
fi

say "The container is now running this working tree's compiler"

say "Compiling again, with the same options"
if ! compose exec -T joomla php "${WEBROOT}/cli/joomla.php" \
	${COMPILE_COMMAND} > "${OUT_DIR}/candidate.log" 2>&1
then
	say "The second compile failed. Its output:"
	cat "${OUT_DIR}/candidate.log"
	exit 1
fi

tail -20 "${OUT_DIR}/candidate.log"
take_packages candidate

say "Comparing what the two compilers produced"
GOLDEN="${OUT_DIR}/golden"
mkdir -p "${GOLDEN}"
unpack_packages "${OUT_DIR}/baseline" "${GOLDEN}"

git -C "${GOLDEN}" init -q
git -C "${GOLDEN}" add -A
git -C "${GOLDEN}" \
	-c user.name="golden master" \
	-c user.email="golden@master.invalid" \
	commit -qm "what the released compiler produced"

# Lay the second run over the first, so one diff shows added, removed and
# changed files together - across every package, not just one of them. A package
# that only one of the two runs produced shows up as wholly added or removed,
# which is exactly what it is.
find "${GOLDEN}" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
unpack_packages "${OUT_DIR}/candidate" "${GOLDEN}"
git -C "${GOLDEN}" add -A

git -C "${GOLDEN}" diff --cached --stat > "${OUT_DIR}/summary.txt"
git -C "${GOLDEN}" diff --cached > "${OUT_DIR}/full.diff"

rm -f "${PACKAGE}"

if [[ -s "${OUT_DIR}/summary.txt" ]]
then
	say "The two compilers produced different components"
	cat "${OUT_DIR}/summary.txt"
else
	say "The two compilers produced the same component"
fi

say "Everything is in ${OUT_DIR}"
