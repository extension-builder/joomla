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
#   JOOMLA_VERSION  Compile target, 3 4 5 or 6 (default: 6)
#   COMPILE_EXTRA   Extra options for both compiles (see the default below)
#   KEEP_STACK      Leave the containers running afterwards when set to 1
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/.github/golden-master/docker-compose.yml"
OUT_DIR="${1:-${REPO_ROOT}/.golden-master}"

COMPONENT="${COMPONENT:-1c20aec5-bf1a-44e7-9deb-d1c920ca591d}"
JOOMLA_VERSION="${JOOMLA_VERSION:-6}"
KEEP_STACK="${KEEP_STACK:-0}"

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

# The target being built for. Joomla 6 is what this work is aimed at.
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

# Bring the package the compiler just wrote out of the container.
#
# The compiler says where it put it in the last lines of its own output, so look
# there first. Joomla's tmp folder is where it lands, but that is configuration,
# not a promise, so fall back to looking for it.
#
# $1  a name for this run
# $2  the log holding that compile's output
take_package() {
	local name="$1" log="$2" package

	# Only paths under the site root: the container log also carries the released
	# package the entrypoint installed, and that is not what was just built.
	package="$(grep -oE "${WEBROOT}/[^[:space:]\"']+\.zip" "${log}" 2>/dev/null | tail -1)"

	if [[ -n "${package}" ]] && ! compose exec -T joomla sh -c "test -f '${package}'"
	then
		package=""
	fi

	if [[ -z "${package}" ]]
	then
		package="$(compose exec -T joomla sh -c \
			"ls -1t ${WEBROOT}/tmp/*.zip 2>/dev/null | head -1" | tr -d '\r')"
	fi

	if [[ -z "${package}" ]]
	then
		package="$(compose exec -T joomla sh -c \
			"find ${WEBROOT} -name '*.zip' -newer ${WEBROOT}/configuration.php 2>/dev/null | head -1" \
			| tr -d '\r')"
	fi

	if [[ -z "${package}" ]]
	then
		say "The ${name} compile left no package behind. The last of its output:"
		tail -40 "${log}"
		exit 1
	fi

	say "The ${name} compiler wrote ${package}"
	compose cp "joomla:${package}" "${OUT_DIR}/${name}.zip"
	compose exec -T joomla sh -c "rm -f ${WEBROOT}/tmp/*.zip"
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
take_package baseline "${OUT_DIR}/baseline.log"

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
take_package candidate "${OUT_DIR}/candidate.log"

say "Comparing what the two compilers produced"
GOLDEN="${OUT_DIR}/golden"
mkdir -p "${GOLDEN}"
unzip -q "${OUT_DIR}/baseline.zip" -d "${GOLDEN}"

git -C "${GOLDEN}" init -q
git -C "${GOLDEN}" add -A
git -C "${GOLDEN}" \
	-c user.name="golden master" \
	-c user.email="golden@master.invalid" \
	commit -qm "what the released compiler produced"

# Lay the second component over the first, so one diff shows added, removed and
# changed files together.
find "${GOLDEN}" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
unzip -q "${OUT_DIR}/candidate.zip" -d "${GOLDEN}"
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
