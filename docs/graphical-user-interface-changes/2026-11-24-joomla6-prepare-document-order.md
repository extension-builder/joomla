# Joomla 6 document preparation order

- **Date:** 2026-11-24.
- **Authorization:** The maintainer requested the supplied `_prepareDocument()`
  structure exactly, implemented in the compiler on a new branch from `main`.
- **Purpose:** Initialize dependencies before loaders, include conditional jQuery
  dependencies, and register prerequisite paths before the generated view file.

## Protected paths

The following existing templates change only their `_prepareDocument()` output:

- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`
- `admin/compiler/joomla_6/CUSTOM_ADMIN_VIEWS_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEW_HTML.php`
- `admin/compiler/joomla_6/SITE_VIEWS_HTML.php`

Initialize style dependencies to `[]` and script dependencies to `['core']`
before the existing loaders. Remove the later normalization and fallback block.
Place view-local prerequisite paths before the generated view JavaScript file,
followed by model scripts. Keep the supplied registration guards, formatting,
header checker, document hooks and inline JavaScript.

The existing Joomla 6 JavaScript emitter moves an authored PHP Document block
made entirely of literal script includes into that prerequisite list. This
puts the supplied Marked include before the generated Compiler script on the
next build. Other authored PHP remains intact.

No protected files are created, moved, renamed or deleted. No controls, labels,
layout or accessibility markup change. Joomla 3–5 keep their existing output.

## Verification

Existing template, loader and generated-browser tests cover initialization,
jQuery settings, prerequisite execution, duplicate paths and existing assets.
The compiler golden comparison checks all four Joomla targets. Results are
reported by the branch's pull-request checks.

## Source reconciliation

These four files are the repository's Joomla 6 compiler template inputs; the
change is made in those inputs so subsequent compilations use the new order.
The matching maintained template export must receive the same delta; its
external source mapping retains the status recorded in the preceding change
record. Roll back the template and emitter changes together.
