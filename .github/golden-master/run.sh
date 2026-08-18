#!/usr/bin/env bash
#
# Compile one component twice — once with the released compiler, once with this
# working tree — and report what changed in the component that came out.
#
# The released compiler comes from the octoleo/joomengine image, which ships
# Joomla with the last JCB release already installed. The working tree replaces
# it in place: JCB's compiler lives in libraries/vendor_jcb, which the component
# manifest does not ship, so installing this repository as an extension would
# leave the released compiler running. The files are copied over instead.
#
# usage: .github/golden-master/run.sh [output-directory]
#
# Environment:
#   COMPONENT       GUID of the component to compile (default: the demo one)
#   JOOMLA_VERSION  Compile target, 3 4 5 or 6 (default: 5)
#   KEEP_STACK      Leave the containers running afterwards when set to 1
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${REPO_ROOT}/.github/golden-master/docker-compose.yml"
OUT_DIR="${1:-${REPO_ROOT}/.golden-master}"

COMPONENT="${COMPONENT:-1c20aec5-bf1a-44e7-9deb-d1c920ca591d}"
JOOMLA_VERSION="${JOOMLA_VERSION:-5}"
KEEP_STACK="${KEEP_STACK:-0}"

WEBROOT=/var/www/html
READY_TIMEOUT=600

# Every option that could differ between two runs for a reason that is not the
# compiler's own doing is pinned, so a difference in the diff means a
# difference in the compiler.
#
#   debug-line-nr  writes the class and line that emitted each line into the
#                  output. Moving a method to a new class changes every one of
#                  those markers, which would bury the real diff.
#   build-date     is stamped into what is generated, so it must not be "now".
#   minify         would compare minified javascript, which is unreadable.
#   powers-repository reaches out to a remote, which we neither need nor want.
COMPILE_OPTIONS=(
	--component="${COMPONENT}"
	--joomla-version="${JOOMLA_VERSION}"
	--debug-line-nr=0
	--add-build-date=2
	--build-date=2026-01-01
	--minify=0
	--add-placeholders=0
	--powers=1
	--powers-repository=0
	--indentation-value=1
	--no-interaction
)

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

say "Starting Joomla with the released compiler"
compose up -d

say "Waiting for Joomla and JCB to finish installing"
deadline=$(( SECONDS + READY_TIMEOUT ))
until compose exec -T joomla test -f "${WEBROOT}/configuration.php" 2>/dev/null \
	&& compose exec -T joomla test -d "${WEBROOT}/libraries/vendor_jcb" 2>/dev/null
do
	if (( SECONDS > deadline ))
	then
		say "Joomla did not come up within ${READY_TIMEOUT}s. Container log:"
		compose logs joomla | tail -80
		exit 1
	fi
	sleep 5
done

mkdir -p "${OUT_DIR}"
rm -rf "${OUT_DIR:?}/"*

# Compile the component and bring the package it produced back out.
#
# $1  a name for this run, used for the log and the copied package
compile() {
	local name="$1"

	say "Compiling with the ${name} compiler"
	compose exec -T joomla rm -rf "${WEBROOT}/tmp" >/dev/null 2>&1 || true
	compose exec -T joomla mkdir -p "${WEBROOT}/tmp"

	if ! compose exec -T joomla php "${WEBROOT}/cli/joomla.php" \
		componentbuilder:compile:component "${COMPILE_OPTIONS[@]}" \
		> "${OUT_DIR}/${name}.log" 2>&1
	then
		say "The ${name} compile failed. Its output:"
		cat "${OUT_DIR}/${name}.log"
		exit 1
	fi

	tail -20 "${OUT_DIR}/${name}.log"

	local package
	package="$(compose exec -T joomla sh -c "ls -1 ${WEBROOT}/tmp/*.zip 2>/dev/null | head -1" | tr -d '\r')"

	if [[ -z "${package}" ]]
	then
		say "The ${name} compile wrote no package into ${WEBROOT}/tmp. Its output:"
		cat "${OUT_DIR}/${name}.log"
		exit 1
	fi

	say "The ${name} compiler wrote ${package}"
	compose cp "joomla:${package}" "${OUT_DIR}/${name}.zip"
}

compile baseline

say "Replacing the released compiler with this working tree"
# The compiler and everything it uses lives here.
compose exec -T joomla rm -rf "${WEBROOT}/libraries/vendor_jcb"
compose cp "${REPO_ROOT}/libraries/vendor_jcb" "joomla:${WEBROOT}/libraries/vendor_jcb"
# The component side, for the parts of a compile that run through it.
compose cp "${REPO_ROOT}/admin/." \
	"joomla:${WEBROOT}/administrator/components/com_componentbuilder/"
compose exec -T joomla sh -c "chown -R www-data:www-data ${WEBROOT}/libraries/vendor_jcb ${WEBROOT}/administrator/components/com_componentbuilder"

# Prove the swap took, rather than trusting it.
if ! compose exec -T joomla test -f \
	"${WEBROOT}/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Helper/Interpretation.php"
then
	say "The working tree did not land in the container"
	exit 1
fi

compile candidate

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

# Lay the second component over the first, so the diff shows added, removed and
# changed files in one place.
find "${GOLDEN}" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
unzip -q "${OUT_DIR}/candidate.zip" -d "${GOLDEN}"
git -C "${GOLDEN}" add -A

git -C "${GOLDEN}" diff --cached --stat > "${OUT_DIR}/summary.txt"
git -C "${GOLDEN}" diff --cached > "${OUT_DIR}/full.diff"

if [[ -s "${OUT_DIR}/summary.txt" ]]
then
	say "The two compilers produced different components"
	cat "${OUT_DIR}/summary.txt"
else
	say "The two compilers produced the same component"
fi

say "Everything is in ${OUT_DIR}"
