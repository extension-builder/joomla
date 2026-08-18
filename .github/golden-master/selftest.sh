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
			# compose exec -T joomla sh -c "<command>"
			local command="${!#}"
			command="${command//${WEBROOT}\/tmp/${STUB_TMP}}"
			sh -c "${command}"
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
expect_exit "says nothing about a package with no target in its name" 0 \
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
printf '%d passed, %d failed\n' "${PASSED}" "${FAILED}"
(( FAILED == 0 ))
