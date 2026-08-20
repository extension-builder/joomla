#!/usr/bin/env bash
#
# Run the golden master's own machinery without docker, a container, or a
# compile, and check it does what it says.
#
# This exists because the run itself takes a minute and a half in CI, and a
# typo in a variable name should not cost that. The bug that prompted it was
# `local name="$1" dest="${OUT_DIR}/${name}"`, which bash rejects under set -u:
# every word of a `local` line is expanded before any of them is assigned, so
# the second read an unset first. Nothing but running the function finds that.
#
# usage: .github/golden-master/selftest.sh
#
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "${WORK}"' EXIT

OUT_DIR="${WORK}/out"
WEBROOT=/var/www/html
COMPOSE_FILE="${HERE}/docker-compose.yml"
KEEP_STACK=1
INSTALL_TIMEOUT=5
JOOMLA_VERSION=6

mkdir -p "${OUT_DIR}"

# shellcheck source=.github/golden-master/lib.sh
source "${HERE}/lib.sh"

# The container, as far as these functions can tell. STUB_TMP stands in for the
# site's tmp folder and STUB_LOG for what the container has said so far.
STUB_TMP="${WORK}/container-tmp"
STUB_LOG="${WORK}/container.log"
mkdir -p "${STUB_TMP}"
: > "${STUB_LOG}"

compose() {
	case "$1 ${2:-}" in
		'logs joomla')
			cat "${STUB_LOG}"
			;;
		'exec -T')
			# take_packages runs `sh -c "<command>"`; run_compile runs php with
			# the compile command, and only its exit status matters here.
			if [[ "$4" == 'sh' ]]
			then
				local command="${!#}"
				command="${command//${WEBROOT}\/tmp/${STUB_TMP}}"
				sh -c "${command}"
			else
				compose_exec_stub
			fi
			;;
		'cp '*)
			cp "${2#joomla:}" "$3"
			;;
		*)
			return 0
			;;
	esac
}

PASSED=0
FAILED=0

check() {
	local what="$1" outcome="$2"

	if [[ "${outcome}" == "pass" ]]
	then
		printf '  ok    %s\n' "${what}"
		PASSED=$(( PASSED + 1 ))
	else
		printf '  FAIL  %s\n' "${what}"
		FAILED=$(( FAILED + 1 ))
	fi
}

# Run something that is expected to call exit, and report whether it did.
#
# $1  what to call it
# $2  the exit status wanted
# $3+ the command
expect_exit() {
	local what="$1" wanted="$2"
	shift 2

	local got=0
	( "$@" ) >/dev/null 2>&1 || got=$?

	[[ "${got}" == "${wanted}" ]] && check "${what}" pass || check "${what}" fail
}

echo "wait_for_log"
printf '[INFO] Joomla CLI command succeeded: extension:install --path /usr/src/joomengine/jcb.zip --no-interaction\n' >> "${STUB_LOG}"
expect_exit "returns once the marker is in the log" 0 \
	wait_for_log 'Joomla CLI command succeeded: extension:install --path /usr/src/joomengine/jcb.zip' 'the released JCB is installed'

printf '[ERROR] Joomla CLI command failed: componentbuilder:compile:component\n' >> "${STUB_LOG}"
expect_exit "stops when the container reports a failed command" 1 \
	wait_for_log 'a marker that will never appear' 'something that never happens'

: > "${STUB_LOG}"
expect_exit "gives up rather than waiting for ever" 1 \
	wait_for_log 'a marker that will never appear' 'something that never happens'

echo
echo "pull_command"
[[ "$(pull_command aaa bbb)" == 'componentbuilder:pull:joomla_component -i aaa -r bbb --no-interaction' ]] \
	&& check "names the component and the repository it comes from" pass \
	|| check "names the component and the repository it comes from" fail
[[ "$(pull_command aaa bbb)" != *'--items'* ]] \
	&& check "uses the options the pull command declares" pass \
	|| check "uses the options the pull command declares" fail
[[ -z "$(pull_command aaa '')" ]] \
	&& check "asks for nothing when the component is already local" pass \
	|| check "asks for nothing when the component is already local" fail

echo
echo "run_cli"
COMPILE_STUB_STATUS=0
compose_exec_stub() { return "${COMPILE_STUB_STATUS}"; }
expect_exit "keeps going when the command succeeds" 0 \
	run_cli fetch 'Fetching the component' 'componentbuilder:pull:joomla_component -i a -r b'
[[ -f "${OUT_DIR}/fetch.log" ]] \
	&& check "keeps what the command said" pass || check "keeps what the command said" fail

COMPILE_STUB_STATUS=1
expect_exit "stops when the command fails" 1 \
	run_cli fetch 'Fetching the component' 'componentbuilder:pull:joomla_component -i a -r b'

# An optional command the console does not have is the harness asking for
# something this JCB cannot do. Anything else that goes wrong still stops.
compose_exec_stub() {
	echo 'Command "componentbuilder:pull:joomla_component" is not defined.'

	return "${COMPILE_STUB_STATUS}"
}
expect_exit "carries on when an optional command is not defined" 0 \
	run_cli fetch 'Fetching the component' 'componentbuilder:pull:joomla_component -i a -r b' optional

compose_exec_stub() {
	echo 'Could not reach the repository.'

	return "${COMPILE_STUB_STATUS}"
}
expect_exit "stops when an optional command fails for any other reason" 1 \
	run_cli fetch 'Fetching the component' 'componentbuilder:pull:joomla_component -i a -r b' optional

compose_exec_stub() { return "${COMPILE_STUB_STATUS}"; }
COMPILE_STUB_STATUS=0

echo
echo "run_compile"
expect_exit "keeps going when the compile succeeds" 0 run_compile ok 'compile:me'
[[ -f "${OUT_DIR}/ok.log" ]] \
	&& check "keeps what the compile said" pass || check "keeps what the compile said" fail

COMPILE_STUB_STATUS=1
expect_exit "stops when the compile fails" 1 run_compile bad 'compile:me'

COMPILE_STUB_STATUS=0

echo
echo "take_packages"
touch "${STUB_TMP}/com_demo_v1_0_0__J6.zip" \
	"${STUB_TMP}/plg_console_democommands_v2_0_0__J6.zip" \
	"${STUB_TMP}/mod_demo_v1_0_0__J6.zip"

expect_exit "takes every package a compile wrote" 0 take_packages baseline
[[ $(find "${OUT_DIR}/baseline" -name '*.zip' | wc -l) == 3 ]] \
	&& check "all three arrived" pass || check "all three arrived" fail
[[ -z "$(ls -A "${STUB_TMP}")" ]] \
	&& check "the site's tmp folder is left clear for the next run" pass \
	|| check "the site's tmp folder is left clear for the next run" fail

expect_exit "stops when a compile wrote nothing" 1 take_packages empty

echo
echo "assert_target"
expect_exit "passes when every package says J6" 0 \
	assert_target x /t/com_demo_v1_0_0__J6.zip /t/plg_demo_v2_0_0__J6.zip
expect_exit "stops when a package says J5" 1 \
	assert_target x /t/com_demo_v1_0_0__J6.zip /t/plg_demo_v2_0_0__J5.zip
expect_exit "stops when a package does not say what it was built for" 1 \
	assert_target x /t/something.zip

echo
echo "unpack_packages"
mkdir -p "${WORK}/src/one" "${WORK}/src/two" "${WORK}/zips" "${WORK}/laid-out"
echo one > "${WORK}/src/one/file.txt"
echo two > "${WORK}/src/two/file.txt"
( cd "${WORK}/src/one" && zip -qr "${WORK}/zips/com_demo_v1_0_0__J6.zip" . )
( cd "${WORK}/src/two" && zip -qr "${WORK}/zips/plg_demo_v2_0_0__J6.zip" . )

unpack_packages "${WORK}/zips" "${WORK}/laid-out"
[[ -f "${WORK}/laid-out/com_demo_v1_0_0__J6/file.txt" && -f "${WORK}/laid-out/plg_demo_v2_0_0__J6/file.txt" ]] \
	&& check "each package gets its own place" pass || check "each package gets its own place" fail
[[ $(cat "${WORK}/laid-out/com_demo_v1_0_0__J6/file.txt") == one ]] \
	&& check "packages do not overwrite each other" pass || check "packages do not overwrite each other" fail

unpack_packages "${WORK}/empty-does-not-exist" "${WORK}/laid-out"
check "an empty run is not an error" pass

echo
echo "log_diff"
DIFF_FILE="${WORK}/full.diff"
{
	printf 'diff --git a/com_demo/one.php b/com_demo/one.php\n'
	printf 'index 111..222 100644\n'
	for i in $(seq 1 10); do printf '+line %d of one\n' "$i"; done
	printf 'diff --git a/com_demo/two.php b/com_demo/two.php\n'
	printf 'index 333..444 100644\n'
	for i in $(seq 1 10); do printf '+line %d of two\n' "$i"; done
} > "${DIFF_FILE}"

OUT="$(log_diff "${DIFF_FILE}" 100 1000)"
grep -q 'com_demo/one.php' <<< "${OUT}" && grep -q 'com_demo/two.php' <<< "${OUT}" \
	&& check "names every file that changed" pass \
	|| check "names every file that changed" fail
grep -q 'line 10 of two' <<< "${OUT}" \
	&& check "prints what changed" pass || check "prints what changed" fail
grep -q '::group::' <<< "${OUT}" \
	&& check "folds itself away in the workflow log" pass \
	|| check "folds itself away in the workflow log" fail

OUT="$(log_diff "${DIFF_FILE}" 3 1000)"
grep -q '8 more lines of this file' <<< "${OUT}" \
	&& check "says how much of a long file it left out" pass \
	|| check "says how much of a long file it left out" fail

OUT="$(log_diff "${DIFF_FILE}" 100 5)"
grep -q '1 more changed file' <<< "${OUT}" \
	&& check "says how many files it left out" pass \
	|| check "says how many files it left out" fail

: > "${WORK}/empty.diff"
[[ -z "$(log_diff "${WORK}/empty.diff" 100 1000)" ]] \
	&& check "says nothing when nothing changed" pass \
	|| check "says nothing when nothing changed" fail

echo
printf '%d passed, %d failed\n' "${PASSED}" "${FAILED}"
(( FAILED == 0 ))
