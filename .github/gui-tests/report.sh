#!/usr/bin/env bash
#
# Turn a Playwright JSON report into the workflow's step summary: the counts,
# and for anything that failed, which test and what it said — the log report
# a person acts on without downloading the artifact.
#
# usage: .github/gui-tests/report.sh path/to/results.json
#
set -euo pipefail

RESULTS="${1:?usage: report.sh path/to/results.json}"

echo "### GUI tests"
echo

if [[ ! -f "${RESULTS}" ]]
then
	echo "The suite did not get as far as producing a report. Read the job log."
	exit 0
fi

jq -r '
	def specs: .. | objects | select(has("specs")) | .specs[];

	def outcome:
		if ([.tests[].results[]] | length) == 0 then "notrun"
		elif ([.tests[].results[-1].status] | all(. == "passed")) then "passed"
		elif ([.tests[].results[].status] | any(. == "passed")) then "flaky"
		else "failed"
		end;

	([specs] | length) as $total
	| ([specs | select(outcome == "passed")] | length) as $passed
	| ([specs | select(outcome == "flaky")]) as $flaky
	| ([specs | select(outcome == "failed")]) as $failed
	| ([specs | select(outcome == "notrun")]) as $notrun

	| "**\($passed) of \($total) passed**"
		+ (if ($flaky | length) > 0 then ", \($flaky | length) flaky" else "" end)
		+ (if ($failed | length) > 0 then ", \($failed | length) failed" else "" end)
		+ (if ($notrun | length) > 0 then ", \($notrun | length) did not run" else "" end),
	"",
	(
		$failed[]
		| "- **failed:** \(.title) — `\(.file)`",
			(
				.tests[].results[-1].error.message // empty
				| "  ```", "  " + (split("\n") | join("\n  ")), "  ```"
			)
	),
	(
		$flaky[]
		| "- **flaky (passed on retry):** \(.title) — `\(.file)`"
	),
	(
		$notrun[]
		| "- **did not run:** \(.title) — `\(.file)`"
	)
' "${RESULTS}"
