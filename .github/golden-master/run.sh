#!/usr/bin/env bash
#
# Compile the same component with the selected baseline and working tree.
# Fail on changed output or final Power identity/placement evidence.
#
# The released compiler comes from the octoleo/joomengine image. Its entrypoint
# installs Joomla, installs the released JCB package, and then runs whatever
# JOOMLA_CLI_COMMANDS holds, which is where the component is fetched. That takes
# a while, and the entrypoint says so in the container log when each step is
# done, so this script waits for those lines rather than guessing.
#
# Both compiles are then driven from here, one before this working tree is
# installed and one after. Driving one of them through the entrypoint and the
# other by hand would put the harness itself into the comparison.
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
#   COMPONENT       GUID of the component to compile
#   REPOSITORY      GUID of the repository to fetch that component from. Set it
#                   empty to skip the fetch, for a component the site already
#                   has
#   COMPILE_EXTRA   Extra --name=value options, identical for both compiles
#   TARGET_JOOMLA   Generated Joomla major (3, 4, 5 or 6; host remains Joomla 6)
#   BASELINE_REF    Optional source commit installed before the baseline build
#   RUN_SCALE       Run the installed discovery/SQL probe on emitted libraries
#   GITHUB_TOKEN     Optional read-only token for fetching public fixture dependencies
#   KEEP_STACK      Leave the containers running afterwards when set to 1
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/.github/golden-master/docker-compose.yml"
OUT_DIR="${1:-${REPO_ROOT}/.golden-master}"

COMPONENT="${COMPONENT:-160d0efb-6bf0-48eb-8d46-55cf74729501}"
REPOSITORY="${REPOSITORY-ca50a886-0fd9-4fd8-803f-ba2cd9f43f55}"
KEEP_STACK="${KEEP_STACK:-0}"
BASELINE_REF="${BASELINE_REF:-}"
RUN_SCALE="${RUN_SCALE:-0}"

# The installed host and generated target are independent axes. Each emitted
# archive must identify the requested generated major.
JOOMLA_VERSION="${TARGET_JOOMLA:-6}"
if [[ ! "${JOOMLA_VERSION}" =~ ^[3456]$ ]]
then
	printf 'TARGET_JOOMLA must be 3, 4, 5 or 6.\n' >&2
	exit 2
fi

WEBROOT=/var/www/html
INSTALL_TIMEOUT=900

# Both compiles must be given the same options, and two of them matter.
#
#   debug-line-nr  writes the class and line that emitted each generated line
#                  into the output. Moving a method to another class changes
#                  every one of those markers, which would bury the real diff.
#   build-date     is stamped into what is generated, so it must not be "now",
#                  or the two runs differ for no reason worth reading.
#
# The common evidence driver sets exact request values, including explicit 0.
COMPILE_EXTRA="${COMPILE_EXTRA:---debug-line-nr=0 --add-build-date=2 --build-date=2026-01-01}"

# The target is not something to pass in. Say so before adding our own, or the
# check has to tell our flag from theirs.
if [[ " ${COMPILE_EXTRA} " =~ [[:space:]](--joomla-version|-j)([=[:space:]]|$) ]]
then
	printf 'COMPILE_EXTRA must not set a Joomla version. This harness builds for Joomla %s.\n' \
		"${JOOMLA_VERSION}" >&2
	exit 2
fi

# The evidence driver receives the target separately and retains final Power
# state before resetting the build scope.

# shellcheck source=.github/golden-master/lib.sh
source "$(dirname "${BASH_SOURCE[0]}")/lib.sh"

# The compile we run twice, and the fetch the container runs once before either
# of them. Fetching once rather than before each compile is not only cheaper: it
# is what makes the comparison mean anything, since both compilers then read the
# same component out of the same database.
# How much of the diff to print into the log. The whole of it is in the
# artifact either way; these keep a large diff from burying the rest of the log.
DIFF_LINES_PER_FILE="${DIFF_LINES_PER_FILE:-80}"
DIFF_LINES_TOTAL="${DIFF_LINES_TOTAL:-2000}"

COMPILE_COMMAND="componentbuilder:compile:component --component=${COMPONENT} ${COMPILE_EXTRA}"
PULL_COMMAND="$(pull_command "${COMPONENT}" "${REPOSITORY}")"

trap cleanup EXIT

mkdir -p "${OUT_DIR}"
rm -rf "${OUT_DIR:?}/"*

say "Starting Joomla, which installs the released JCB"
say "Compile command: ${COMPILE_COMMAND}"

if [[ -n "${PULL_COMMAND}" ]]
then
	say "Fetch command: ${PULL_COMMAND}"
else
	say "No repository given, so the component must already be on the site"
fi

compose up -d

# The entrypoint installs the released JCB package, and nothing can be asked of
# JCB until it has.
wait_for_log \
	'Joomla CLI command succeeded: extension:install --path /usr/src/joomengine/jcb.zip' \
	'the released JCB is installed'

compose exec -T joomla touch /tmp/jcb-disposable-gui-stack
for driver in bootstrap cli compile-evidence
do
	compose cp "${REPO_ROOT}/.github/golden-master/${driver}.php" "joomla:/tmp/${driver}.php"
done

install_package() {
	local package="$1" name="$2"
	compose cp "${package}" "joomla:/tmp/jcb-${name}.zip"
	if ! compose exec -T joomla php "${WEBROOT}/cli/joomla.php" \
		extension:install --path "/tmp/jcb-${name}.zip" --no-interaction \
		> "${OUT_DIR}/install-${name}.log" 2>&1
	then
		cat "${OUT_DIR}/install-${name}.log"
		exit 1
	fi
	verify_package "${package}" "${name}"
}

# Check every installed first-party compiler/library file, including newly
# extracted helpers. One unchanged legacy file cannot prove deployment.
verify_package() {
	local package="$1" name="$2"
	local verify="${OUT_DIR}/verify-${name}"
	mkdir -p "${verify}"
	unzip -q "${package}" 'libraries/vendor_jcb/VDM.Joomla/src/*' -d "${verify}"
	(
		cd "${verify}"
		find libraries/vendor_jcb/VDM.Joomla/src -type f -print0 \
			| sort -z | xargs -0 sha256sum
	) > "${OUT_DIR}/${name}-installed.sha256"
	compose cp "${OUT_DIR}/${name}-installed.sha256" "joomla:/tmp/jcb-${name}-installed.sha256"
	if ! compose exec -T joomla sh -c \
		"cd ${WEBROOT} && sha256sum -c /tmp/jcb-${name}-installed.sha256" \
		> "${OUT_DIR}/verify-${name}.log" 2>&1
	then
		cat "${OUT_DIR}/verify-${name}.log"
		exit 1
	fi
	rm -rf "${verify}"
}

compile_evidence() {
	local name="$1"
	if ! compose exec -T -e JCB_DISPOSABLE_TEST=1 -e GITHUB_TOKEN joomla \
		php -d memory_limit=1536M /tmp/compile-evidence.php \
		"${COMPONENT}" "${JOOMLA_VERSION}" "/tmp/jcb-golden-${name}.json" \
		${COMPILE_EXTRA} > "${OUT_DIR}/${name}.log" 2>&1
	then
		cat "${OUT_DIR}/${name}.log"
		exit 1
	fi
	compose cp "joomla:/tmp/jcb-golden-${name}.json" "${OUT_DIR}/${name}-powers.json"
	tail -20 "${OUT_DIR}/${name}.log"
}

if [[ -n "${BASELINE_REF}" ]]
then
	BASELINE_SHA="$(git -C "${REPO_ROOT}" rev-parse --verify "${BASELINE_REF}^{commit}")"
	git -C "${REPO_ROOT}" archive --format=zip -o "${OUT_DIR}/baseline-source.zip" "${BASELINE_SHA}"
	zip -dq "${OUT_DIR}/baseline-source.zip" '.github/*' 'libraries/vendor_jcb/tests/*'
	install_package "${OUT_DIR}/baseline-source.zip" baseline
	printf '%s\n' "${BASELINE_SHA}" > "${OUT_DIR}/baseline-source.txt"
else
	printf '%s\n' 'Released compiler from pinned image' > "${OUT_DIR}/baseline-source.txt"
fi
git -C "${REPO_ROOT}" rev-parse HEAD > "${OUT_DIR}/candidate-source.txt"

# Fetch the component through the Joomla console, in a process of its own. It
# has to be a separate process from the compile: a compile that fetches the
# component itself does both jobs at once and runs the site out of memory.
#
# The wrapper sets the existing GitHub client parameter only in process memory.
# The full compile requires a unique local component, even when pull returns 0
# for a Not Found result.
if [[ -n "${PULL_COMMAND}" ]]
then
	say "Fetching the component"
	if ! compose exec -T -e JCB_DISPOSABLE_TEST=1 -e GITHUB_TOKEN joomla \
		php /tmp/cli.php ${PULL_COMMAND} > "${OUT_DIR}/fetch.log" 2>&1
	then
		cat "${OUT_DIR}/fetch.log"
		exit 1
	fi
	tail -20 "${OUT_DIR}/fetch.log"
fi

# Populate any missing public dependencies once, then pin the exact database
# snapshot for both measured compiles. Fetching never occurs inside harvest.
compile_evidence warmup
take_packages warmup
compose exec -T mariadb sh -c \
	'MYSQL_PWD="$MARIADB_PASSWORD" mariadb-dump -u "$MARIADB_USER" "$MARIADB_DATABASE"' \
	> "${OUT_DIR}/definitions.sql"
compile_evidence baseline
take_packages baseline

say "Packaging this working tree"
PACKAGE="${OUT_DIR}/jcb-under-test.zip"
(
	cd "${REPO_ROOT}"
	# The test suite carries its own composer vendor tree, which is enormous and
	# is no part of what JCB installs.
	zip -qr "${PACKAGE}" . \
		-x '.git/*' '.github/*' '.golden-master/*' '.gui-tests/*' 'libraries/vendor_jcb/tests/*'
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
verify_package "${PACKAGE}" candidate

say "The container is now running this working tree's compiler"

# Restore the measured baseline's definitions after candidate installation.
compose exec -T mariadb sh -c \
	'MYSQL_PWD="$MARIADB_PASSWORD" mariadb -u "$MARIADB_USER" "$MARIADB_DATABASE"' \
	< "${OUT_DIR}/definitions.sql"
compile_evidence candidate
take_packages candidate
rm -f "${OUT_DIR}/definitions.sql" "${OUT_DIR}/baseline-source.zip"

compose logs joomla > "${OUT_DIR}/container.log" 2>&1

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

if ! diff -u "${OUT_DIR}/baseline-powers.json" "${OUT_DIR}/candidate-powers.json" \
	> "${OUT_DIR}/powers.diff"
then
	cat "${OUT_DIR}/powers.diff"
	printf 'Final compiler Power identity/placement evidence differs.\n' >&2
	exit 1
fi

rm -f "${PACKAGE}"

if [[ -s "${OUT_DIR}/summary.txt" ]]
then
	say "The two compilers produced different components"
	cat "${OUT_DIR}/summary.txt"

	# and what changed, here in the log rather than only in the artifact
	log_diff "${OUT_DIR}/full.diff" "${DIFF_LINES_PER_FILE}" "${DIFF_LINES_TOTAL}"
	exit 1
else
	say "The two compilers produced the same component"
fi

if [[ "${RUN_SCALE}" == 1 ]]
then
	compose cp "${REPO_ROOT}/.github/gui-tests/discovery-scale.php" joomla:/tmp/discovery-scale.php
	compose cp "${REPO_ROOT}/.github/golden-master/scale-evidence.php" joomla:/tmp/scale-evidence.php
	compose exec -T joomla mkdir -p /tmp/jcb-golden-output
	compose cp "${OUT_DIR}/candidate/." joomla:/tmp/jcb-golden-output
	if ! compose exec -T -e JCB_DISPOSABLE_TEST=1 \
		-e "JCB_TARGET_VERSION=${JOOMLA_VERSION}" \
		-e JCB_COMPILER_EVIDENCE=/tmp/jcb-golden-candidate.json \
		-e "JCB_SOURCE_REVISION=$(git -C "${REPO_ROOT}" rev-parse HEAD)" joomla \
		php /tmp/scale-evidence.php "${COMPONENT}" \
		> "${OUT_DIR}/discovery-scale.json" 2> "${OUT_DIR}/discovery-scale.log"
	then
		cat "${OUT_DIR}/discovery-scale.log"
		exit 1
	fi
fi

say "Everything is in ${OUT_DIR}"
