#!/usr/bin/env bash
#
# What the golden master run is made of. Kept apart from run.sh so selftest.sh
# can exercise these without docker, a container, or a compile.
#
# The caller sets OUT_DIR, WEBROOT, COMPOSE_FILE, KEEP_STACK, INSTALL_TIMEOUT
# and JOOMLA_VERSION before sourcing this.

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

# Build the command that fetches a component before anything tries to compile it.
#
# The compile command resolves a component GUID through a service that looks
# locally and then remotely, so compiling a component this site has never seen
# makes one process do the fetching and the compiling both. That is what runs
# the site out of memory. Fetching first, in a process of its own, means the
# compile only ever reads what is already here.
#
# $1  the component GUID
# $2  the repository GUID it can be found in, or empty when it is already local
pull_command() {
	local component="$1" repository="$2"

	if [[ -z "${repository}" ]]
	then
		return 0
	fi

	printf 'componentbuilder:pull:component --items=%s --repo=%s' \
		"${component}" "${repository}"
}

# Run one compile inside the container and keep what it said.
#
# Both compiles go through here, so the only difference between them is which
# compiler is installed at the time. A compile driven one way and compared
# against one driven another way would be comparing the harness as much as the
# compilers.
#
# $1  a name for this run
# $2  the compile command
run_compile() {
	local name="$1" command="$2"
	local log="${OUT_DIR}/${name}.log"

	say "Compiling with the ${name} compiler"

	if ! compose exec -T joomla php "${WEBROOT}/cli/joomla.php" \
		${command} > "${log}" 2>&1
	then
		say "The ${name} compile failed. Its output:"
		cat "${log}"
		exit 1
	fi

	tail -20 "${log}"
}

# Bring out everything the compiler just wrote.
#
# One compile can leave several packages behind - the component itself, and a
# package for each module and plugin it builds. Taking only the newest compares
# one of them and calls it the component, so take the lot.
#
# $1  a name for this run
take_packages() {
	# One name per statement. Bash expands every word of a `local` line before it
	# assigns any of them, so `local a="$1" b="${a}"` reads an unset a.
	local name="$1"
	local dest="${OUT_DIR}/${name}"
	local package
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
# rather than from the flag we handed it, which is the whole point: the flag is
# what we asked for, the suffix is what we got.
#
# A package with no suffix at all is not evidence of anything, and this asks for
# evidence, so that fails too. If a package legitimately carries no target in
# its name, this is the place to say so and why.
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

		if [[ ! "${base}" =~ __J([0-9]+)$ ]]
		then
			printf '    does not say what it was built for: %s\n' "${base}"
			wrong=1
		elif [[ "${BASH_REMATCH[1]}" != "${JOOMLA_VERSION}" ]]
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

# Print the diff between the two compiles into the workflow log.
#
# The whole diff goes to the artifact, but reading it there means downloading
# it, so what changed is printed here too. A diff can be very large, so each
# file is given a budget and the whole printout another; whatever is left out
# is said plainly rather than quietly dropped.
#
# $1  the diff to print
# $2  how many lines to print of any one file
# $3  how many lines to print in total
log_diff() {
	local diff="$1" per_file="$2" total="$3"

	if [[ ! -s "${diff}" ]]
	then
		return 0
	fi

	printf '::group::The diff between the two compiles\n'

	awk -v per_file="${per_file}" -v total="${total}" '
		function flush_file() {
			if (path == "") return

			printf "\n--- %s\n", path

			for (i = 1; i <= shown; i++)
			{
				print held[i]
			}

			if (held_count > shown)
			{
				printf "    [%d more lines of this file are in full.diff]\n",
					held_count - shown
			}

			delete held
			shown = 0
			held_count = 0
		}

		/^diff --git / {
			flush_file()

			if (printed >= total)
			{
				skipped++
				path = ""
				next
			}

			path = $0
			sub(/^diff --git a\//, "", path)
			sub(/ b\/.*$/, "", path)
			next
		}

		path != "" {
			held_count++

			if (shown < per_file && printed < total)
			{
				held[++shown] = $0
				printed++
			}
		}

		END {
			flush_file()

			if (skipped > 0)
			{
				printf "\n[%d more changed file(s) are in full.diff]\n", skipped
			}
		}
	' "${diff}"

	printf '::endgroup::\n'
}
