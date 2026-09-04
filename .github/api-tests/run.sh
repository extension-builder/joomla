#!/usr/bin/env bash
#
# Prove the JSON:API of a compiled JCB component through a real HTTP round trip.
#
# A Joomla is installed on a database this script is given, the released JCB
# package goes on it (that is where the console plugin with the compile command
# lives), and this working tree is installed over it the way JCB is installed:
# zipped, and handed to extension:install. The shipped demo component then
# gets the one thing it lacks for an API, a plugin of the webservices group
# carrying [[[API_ROUTES_METHOD]]], is compiled with this working tree's
# compiler, and is installed together with that plugin. A user with an API
# token drives v1/demo/looks through PHP's built-in server:
# libraries/vendor_jcb/tests/api/scenarios.php says what is checked.
#
# No docker: the database is whatever JCB_DB_* names (a GitHub runner's own
# MySQL, a local MariaDB), and the site runs from a temporary directory.
#
# usage: .github/api-tests/run.sh [output-directory]
#
# Environment:
#   JCB_DB_HOST        Database host, with :port when not 3306 (default 127.0.0.1)
#   JCB_DB_USER        Database user that may create the database (default root)
#   JCB_DB_PASS        Its password (default root, as a GitHub runner's MySQL)
#   JCB_DB_NAME        Database to create and use (default jcb_api_tests)
#   JCB_JOOMLA         Joomla release to install (default 6.1.2)
#   JCB_PACKAGE_TAG    Tag of joomengine/pkg-component-builder to install first
#                      (default v6.1.6)
#   JCB_API_PORT       Port the built-in server listens on (default 8090)
#   JCB_API_HAMMER     Concurrent creates in the burst scenario (default 25)
#   JCB_API_REPRODUCE  Set to 1 to assert the create fails the way it did before
#                      the record-key fix, instead of running the scenarios
#   KEEP_SITE          Set to 1 to leave the site and server running afterwards
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
OUT_DIR="${1:-${REPO_ROOT}/.api-tests}"
SUITE_DIR="${REPO_ROOT}/libraries/vendor_jcb/tests/api"

JCB_DB_HOST="${JCB_DB_HOST:-127.0.0.1}"
JCB_DB_USER="${JCB_DB_USER:-root}"
JCB_DB_PASS="${JCB_DB_PASS:-root}"
JCB_DB_NAME="${JCB_DB_NAME:-jcb_api_tests}"
JCB_JOOMLA="${JCB_JOOMLA:-6.1.2}"
JCB_PACKAGE_TAG="${JCB_PACKAGE_TAG:-v6.1.6}"
JCB_API_PORT="${JCB_API_PORT:-8090}"
JCB_API_HAMMER="${JCB_API_HAMMER:-25}"
JCB_API_REPRODUCE="${JCB_API_REPRODUCE:-0}"
KEEP_SITE="${KEEP_SITE:-0}"

DEMO_COMPONENT=1c20aec5-bf1a-44e7-9deb-d1c920ca591d
SITE="${OUT_DIR}/site"
BASE_URL="http://127.0.0.1:${JCB_API_PORT}"
API_USER=jcbapi
API_PASS='Jcb-Api-Tests-2026!'
SERVER_PID=""

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }

cleanup() {
	if [[ -n "${SERVER_PID}" && "${KEEP_SITE}" != "1" ]]
	then
		kill "${SERVER_PID}" >/dev/null 2>&1 || true
	fi
}
trap cleanup EXIT

# Every JCB and Joomla action goes through the site's console, the way a
# person would ask for it. Console commands run with live_site cleared, and
# the server runs with it set: PHP's built-in server reports its SAPI as
# cli-server, which Joomla's Uri::base() reads as a console run and answers
# every route with 404 unless live_site says where the site is.
console() {
	php -d memory_limit=1G "${SITE}/cli/joomla.php" "$@"
}

live_site() {
	sed -i "s#public \$live_site = '[^']*';#public \$live_site = '$1';#" "${SITE}/configuration.php"
}

mkdir -p "${OUT_DIR}"

say "Joomla ${JCB_JOOMLA} into ${SITE}"
if [[ ! -s "${OUT_DIR}/joomla.zip" ]]
then
	curl -fsSL -o "${OUT_DIR}/joomla.zip" \
		"https://github.com/joomla/joomla-cms/releases/download/${JCB_JOOMLA}/Joomla_${JCB_JOOMLA}-Stable-Full_Package.zip"
fi
rm -rf "${SITE}"
mkdir -p "${SITE}"
unzip -q "${OUT_DIR}/joomla.zip" -d "${SITE}"

say "Installing Joomla on ${JCB_DB_NAME} at ${JCB_DB_HOST}"
(
	cd "${SITE}"
	php installation/joomla.php install \
		--site-name="JCB API tests" --admin-user="API Admin" --admin-username=apiadmin \
		--admin-password="${API_PASS}" --admin-email=apiadmin@jcb.invalid \
		--db-type=mysqli --db-host="${JCB_DB_HOST}" --db-user="${JCB_DB_USER}" --db-pass="${JCB_DB_PASS}" \
		--db-name="${JCB_DB_NAME}" --db-prefix=jcb_ --db-encryption=0 --no-interaction
) > "${OUT_DIR}/joomla-install.log" 2>&1
tail -3 "${OUT_DIR}/joomla-install.log"

say "Installing the released JCB package ${JCB_PACKAGE_TAG}, for its console plugin"
rm -rf "${OUT_DIR}/pkg-src"
git clone -q --branch "${JCB_PACKAGE_TAG}" --depth 1 https://github.com/joomengine/pkg-component-builder.git "${OUT_DIR}/pkg-src"
(cd "${OUT_DIR}/pkg-src" && rm -f "${OUT_DIR}/pkg-jcb.zip" && zip -qr "${OUT_DIR}/pkg-jcb.zip" . -x '.git/*')
console extension:install --path "${OUT_DIR}/pkg-jcb.zip" --no-interaction > "${OUT_DIR}/package-install.log" 2>&1
grep -q "installed successfully" "${OUT_DIR}/package-install.log" || { cat "${OUT_DIR}/package-install.log"; exit 1; }

say "Installing this working tree the way JCB is installed"
PACKAGE="${OUT_DIR}/jcb-under-test.zip"
(
	cd "${REPO_ROOT}"
	rm -f "${PACKAGE}"
	zip -qr "${PACKAGE}" . -x '.git/*' '.github/*' '.api-tests/*' '.golden-master/*' '.gui-tests/*' 'libraries/vendor_jcb/tests/*'
)
console extension:install --path "${PACKAGE}" --no-interaction > "${OUT_DIR}/tree-install.log" 2>&1
grep -q "installed successfully" "${OUT_DIR}/tree-install.log" || { cat "${OUT_DIR}/tree-install.log"; exit 1; }
cmp -s "${REPO_ROOT}/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ItemSave.php" \
	"${SITE}/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ItemSave.php" \
	|| { echo "The site does not carry this working tree's compiler."; exit 1; }

say "Linking a webservices plugin to the demo component"
php "${SUITE_DIR}/seed-webservices-plugin.php" "${SITE}" "${DEMO_COMPONENT}"

say "Compiling the demo component for Joomla 6"
rm -f "${SITE}"/tmp/*.zip
console componentbuilder:compile:component --component="${DEMO_COMPONENT}" --joomla-version=6 \
	--add-build-date=2 --build-date=2026-01-01 --no-interaction > "${OUT_DIR}/compile.log" 2>&1 || true
ls -1 "${SITE}"/tmp/*.zip > /dev/null 2>&1 || { echo "The compile left no package."; cat "${OUT_DIR}/compile.log"; exit 1; }

# A power the compiler could not fetch leaves its placeholder in the output,
# and such a component fails at runtime in ways that have nothing to do with
# what is tested here, so say so before installing it.
unresolved=0
for package in "${SITE}"/tmp/*.zip
do
	while IFS= read -r file
	do
		if unzip -p "${package}" "${file}" | grep -q '___Power'
		then
			echo "Unresolved power placeholder in $(basename "${package}"): ${file}"
			unresolved=$((unresolved + 1))
		fi
	done < <(unzip -Z1 "${package}" | grep '\.php$' || true)
done
if [[ "${unresolved}" -gt 0 ]]
then
	echo "The compile could not resolve every power; read ${OUT_DIR}/compile.log."
	exit 1
fi

say "Installing the compiled demo and its webservices plugin"
for package in "${SITE}"/tmp/com_demo_*.zip "${SITE}"/tmp/plg_webservices_*.zip
do
	console extension:install --path "${package}" --no-interaction >> "${OUT_DIR}/demo-install.log" 2>&1
	printf '    %s\n' "$(basename "${package}")"
done
php -r '
require $argv[1] . "/configuration.php";
$c = new JConfig();
[$host, $port] = str_contains($c->host, ":") ? explode(":", $c->host, 2) : [$c->host, 3306];
$db = new mysqli($host, $c->user, $c->password, $c->db, (int) $port);
$db->query("UPDATE `{$c->dbprefix}extensions` SET enabled = 1 WHERE folder = \"webservices\" AND element LIKE \"demo%\"");
echo "webservices plugin enabled\n";
' "${SITE}"

say "A user with an API token"
console user:add --username="${API_USER}" --name="API Tests" --password="${API_PASS}" \
	--email="${API_USER}@jcb.invalid" --usergroup="Super Users" --no-interaction > "${OUT_DIR}/user.log" 2>&1 || true
TOKEN="$(php "${SUITE_DIR}/token.php" "${SITE}" "${API_USER}")"

say "Serving the site on ${BASE_URL}"
live_site "${BASE_URL}"
php -S "127.0.0.1:${JCB_API_PORT}" -t "${SITE}" > "${OUT_DIR}/server.log" 2>&1 &
SERVER_PID=$!
for _ in $(seq 1 30)
do
	if curl -s -o /dev/null "${BASE_URL}/api/index.php/v1/users" -H "X-Joomla-Token: ${TOKEN}"
	then
		break
	fi
	sleep 1
done

if [[ "${JCB_API_REPRODUCE}" == "1" ]]
then
	say "Reproducing the create failure this tree is expected to carry"
	php "${SUITE_DIR}/scenarios.php" "${BASE_URL}" "${TOKEN}" v1/demo/looks --reproduce
else
	say "Driving v1/demo/looks"
	php "${SUITE_DIR}/scenarios.php" "${BASE_URL}" "${TOKEN}" v1/demo/looks --hammer="${JCB_API_HAMMER}"
fi
