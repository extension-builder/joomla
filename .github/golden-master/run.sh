#!/usr/bin/env bash
#
# Compile one component twice — once with the released compiler, once with this
# working tree — and report what changed in the component that came out.
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
#   COMPILE_EXTRA   Extra options for both compiles; a Joomla version flag here
#                   is rejected, since the target is not a choice
#   KEEP_STACK      Leave the containers running afterwards when set to 1
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/.github/golden-master/docker-compose.yml"
OUT_DIR="${1:-${REPO_ROOT}/.golden-master}"

COMPONENT="${COMPONENT:-160d0efb-6bf0-48eb-8d46-55cf74729501}"
REPOSITORY="${REPOSITORY-ca50a886-0fd9-4fd8-803f-ba2cd9f43f55}"
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
#
# One caveat on debug-line-nr, and it is not this script's to fix. The console
# command discards a CLI value of '0', because it filters with !empty() and
# PHP calls '0' empty (Console/Compiler.php, "Release of v6.1.4"). So this asks
# for 0 and gets whatever the global setting says. If a comparison ever comes
# back full of changed // line markers and nothing else, that is why.
COMPILE_EXTRA="${COMPILE_EXTRA:---debug-line-nr=0 --add-build-date=2 --build-date=2026-01-01}"

# The target is not something to pass in. Say so before adding our own, or the
# check has to tell our flag from theirs.
if [[ " ${COMPILE_EXTRA} " =~ [[:space:]](--joomla-version|-j)([=[:space:]]|$) ]]
then
	printf 'COMPILE_EXTRA must not set a Joomla version. This harness builds for Joomla %s.\n' \
		"${JOOMLA_VERSION}" >&2
	exit 2
fi

# And it goes on last: Symfony takes the last value of a repeated option, so a
# version flag that got in ahead of this one would win.
COMPILE_EXTRA="${COMPILE_EXTRA} --joomla-version=${JOOMLA_VERSION}"

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
export JCB_CLI_COMMANDS="${PULL_COMMAND}"

trap cleanup EXIT

mkdir -p "${OUT_DIR}"
rm -rf "${OUT_DIR:?}/"*

say "Starting Joomla, which installs the released JCB and fetches the component"
say "Compile command: ${COMPILE_COMMAND}"

if [[ -n "${PULL_COMMAND}" ]]
then
	say "Fetch command: ${PULL_COMMAND}"
else
	say "No repository given, so the component must already be on the site"
fi

compose up -d

# The entrypoint installs the released JCB package first...
wait_for_log \
	'Joomla CLI command succeeded: extension:install --path /usr/src/joomengine/jcb.zip' \
	'the released JCB is installed'

# ...and only then fetches the component. Waiting for this is the gate: nothing
# compiles until the fetch has said it succeeded.
if [[ -n "${PULL_COMMAND}" ]]
then
	wait_for_log \
		"Joomla CLI command succeeded: ${PULL_COMMAND}" \
		'the component is fetched'
fi

run_compile baseline "${COMPILE_COMMAND}"
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

run_compile candidate "${COMPILE_COMMAND}"
take_packages candidate

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

rm -f "${PACKAGE}"

if [[ -s "${OUT_DIR}/summary.txt" ]]
then
	say "The two compilers produced different components"
	cat "${OUT_DIR}/summary.txt"

	# and what changed, here in the log rather than only in the artifact
	log_diff "${OUT_DIR}/full.diff" "${DIFF_LINES_PER_FILE}" "${DIFF_LINES_TOTAL}"
else
	say "The two compilers produced the same component"
fi

say "Everything is in ${OUT_DIR}"
