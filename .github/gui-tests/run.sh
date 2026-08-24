#!/usr/bin/env bash
#
# Stand up a Joomla with the released JCB, install this working tree over it,
# and run the Playwright GUI suite against the administrator.
#
# The container flow is the golden master's: the joomengine entrypoint installs
# Joomla and the released JCB and says so in its log, this script waits for
# those lines, and this working tree then goes in the way JCB is installed —
# zipped, handed to the container, and installed with the same
# extension:install the entrypoint uses.
#
# What is different is what runs afterwards: a known administrator account is
# guaranteed through the Joomla console, and the Playwright suite in
# libraries/vendor_jcb/tests/gui drives the real administrator through the
# browser — AJAX, pairing board, imports and all.
#
# usage: .github/gui-tests/run.sh [output-directory]
#
# Environment:
#   JCB_BASE_URL              Where the site answers (default http://localhost:8080)
#   JCB_ADMIN_USER            The administrator account the suite logs in with
#   JCB_ADMIN_PASS            Its password
#   PLAYWRIGHT_INSTALL_ARGS   Arguments for `npx playwright install`
#                             (default: chromium; CI passes --with-deps chromium)
#   KEEP_STACK                Leave the containers running afterwards when 1
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/.github/gui-tests/docker-compose.yml"
OUT_DIR="${1:-${REPO_ROOT}/.gui-tests}"
SUITE_DIR="${REPO_ROOT}/libraries/vendor_jcb/tests/gui"

JCB_BASE_URL="${JCB_BASE_URL:-http://localhost:8080}"
JCB_ADMIN_USER="${JCB_ADMIN_USER:-jcbgui}"
JCB_ADMIN_PASS="${JCB_ADMIN_PASS:-Jcb-Gui-Tests-2026!}"
PLAYWRIGHT_INSTALL_ARGS="${PLAYWRIGHT_INSTALL_ARGS:-chromium}"
KEEP_STACK="${KEEP_STACK:-0}"

WEBROOT=/var/www/html
INSTALL_TIMEOUT=900

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }

compose() { docker compose -f "${COMPOSE_FILE}" "$@"; }

cleanup() {
	if [[ "${KEEP_STACK}" != "1" ]]
	then
		say "Removing the stack"
		compose down -v >/dev/null 2>&1 || true
	fi
}

# Wait for a line the entrypoint writes when it finishes a step.
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

# Run one Joomla CLI command inside the container and keep what it said.
# The command comes in as real arguments, so an option value with a space
# in it ("Super Users") survives the trip.
run_cli() {
	local name="$1" what="$2"
	shift 2
	local log="${OUT_DIR}/${name}.log"

	say "${what}"

	if ! compose exec -T joomla php "${WEBROOT}/cli/joomla.php" \
		"$@" > "${log}" 2>&1
	then
		return 1
	fi

	tail -5 "${log}"
}

trap cleanup EXIT

mkdir -p "${OUT_DIR}"
rm -rf "${OUT_DIR:?}/"*

say "Starting Joomla, which installs the released JCB"
compose up -d

wait_for_log \
	'Joomla CLI command succeeded: extension:install --path /usr/src/joomengine/jcb.zip' \
	'the released JCB is installed'

say "Packaging this working tree"
PACKAGE="${OUT_DIR}/jcb-under-test.zip"
(
	cd "${REPO_ROOT}"
	# The test suites carry composer and npm trees that are no part of what
	# JCB installs — the same exclusions the golden master packages with.
	zip -qr "${PACKAGE}" . \
		-x '.git/*' '.github/*' '.golden-master/*' '.gui-tests/*' \
			'libraries/vendor_jcb/tests/*'
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

tail -5 "${OUT_DIR}/install.log"

# Prove the install deployed this working tree, rather than trusting that it
# did: a view file the released JCB does not have must now answer inside the
# container, byte for byte.
PROOF_FILE=admin/src/View/Extrusion/HtmlView.php
LOCAL_SUM="$(md5sum "${REPO_ROOT}/${PROOF_FILE}" | cut -d' ' -f1)"
CONTAINER_SUM="$(compose exec -T joomla md5sum \
	"${WEBROOT}/administrator/components/com_componentbuilder/src/View/Extrusion/HtmlView.php" \
	2>/dev/null | cut -d' ' -f1 | tr -d '\r' || true)"

if [[ "${LOCAL_SUM}" != "${CONTAINER_SUM}" ]]
then
	say "The install did not deploy this working tree"
	printf '  working tree: %s\n  container:    %s\n' "${LOCAL_SUM}" "${CONTAINER_SUM:-missing}"
	exit 1
fi

say "The container is now running this working tree"

# Guarantee the administrator account the suite logs in with. A fresh account
# is added where none exists; where one does, its password is set to the one
# the suite knows.
if ! run_cli user-add "Creating the test administrator" \
	user:add "--username=${JCB_ADMIN_USER}" "--name=${JCB_ADMIN_USER}" \
	"--password=${JCB_ADMIN_PASS}" "--email=${JCB_ADMIN_USER}@jcb.invalid" \
	--usergroup="Super Users" --no-interaction
then
	say "The account exists already, so setting its password instead"

	if ! run_cli user-reset "Setting the test administrator password" \
		user:reset-password "--username=${JCB_ADMIN_USER}" \
		"--password=${JCB_ADMIN_PASS}" --no-interaction
	then
		say "Neither adding nor resetting the administrator worked. The logs:"
		cat "${OUT_DIR}/user-add.log" "${OUT_DIR}/user-reset.log" 2>/dev/null || true
		exit 1
	fi
fi

# A whole-component harvest is one long PHP request; the image's default
# execution and memory limits are sized for page views, not for that.
say "Raising the PHP limits for heavy harvests"
compose exec -T joomla sh -c \
	'printf "max_execution_time=300\nmemory_limit=1024M\n" \
		> /usr/local/etc/php/conf.d/zz-gui-tests.ini && apache2ctl -k graceful' \
	|| say "Could not raise the PHP limits; the image defaults stand"

say "Waiting for the site to answer over HTTP"
DEADLINE=$(( SECONDS + 120 ))
until curl -fsS -o /dev/null "${JCB_BASE_URL}/administrator/index.php"
do
	if (( SECONDS > DEADLINE ))
	then
		say "The site never answered at ${JCB_BASE_URL}"
		exit 1
	fi

	sleep 3
done

say "Running the GUI suite"
(
	cd "${SUITE_DIR}"
	npm ci --no-audit --no-fund
	npx playwright install ${PLAYWRIGHT_INSTALL_ARGS}
)

set +e
(
	cd "${SUITE_DIR}"
	JCB_BASE_URL="${JCB_BASE_URL}" \
	JCB_ADMIN_USER="${JCB_ADMIN_USER}" \
	JCB_ADMIN_PASS="${JCB_ADMIN_PASS}" \
	npx playwright test
)
SUITE_STATUS=$?
set -e

say "Keeping the evidence"
compose logs joomla > "${OUT_DIR}/container.log" 2>&1

for artifact in results.json playwright-report test-results
do
	if [[ -e "${SUITE_DIR}/${artifact}" ]]
	then
		cp -r "${SUITE_DIR}/${artifact}" "${OUT_DIR}/"
	fi
done

rm -f "${PACKAGE}"

if (( SUITE_STATUS != 0 ))
then
	say "The GUI suite failed. Everything it saw is in ${OUT_DIR}"
else
	say "The GUI suite passed. Everything is in ${OUT_DIR}"
fi

exit "${SUITE_STATUS}"
