# Joomla 6 view document assets

## Scope

This change consolidates generated custom-admin and site view styles and scripts
under one template-owned Web Asset Manager binding and one root URL lookup.
The implementation starts from `ceac1f8b7ca468e6d4dea4e57feb39988391606e`.

The affected templates are
`admin/compiler/joomla_6/CUSTOM_ADMIN_VIEW_HTML.php`,
`CUSTOM_ADMIN_VIEWS_HTML.php`, `SITE_VIEW_HTML.php`, and
`SITE_VIEWS_HTML.php` in that directory. The active maintainer request on
23 November 2026 authorizes implementing the investigated plan, pushing
incremental commits to a new branch, and opening a pull request.

## Implementation contract

- Keep view JavaScript file creation, body expansion, dates, and installed paths.
- Move Joomla 6 view-file emission out of the libraries loader into a dedicated
  escaped script-list entry. Select behavior with the compiler target.
- Preserve Joomla 3–5 emission and ordinary admin list/edit behavior.
- Bind the manager before the conditional Joomla 6 jQuery loader uses it.
- Preserve loader, metadata, authored PHP, inline CSS, inline JS, and site-button
  hook order. Preserve the complete existing inline JS block and its receiver.
- Merge required dependencies with existing library seeds; preserve registry
  identities, guarded registration, activation, URLs, and version metadata.
- Keep generic configured-library and module include behavior unchanged.
- Exercise complete assembled blocks with recording and real Joomla managers,
  then run repository unit, style, ownership, GUI/API, and compiler golden gates.

## Definition migration and acceptance status

The Compiler example requires the generated file, its local Marked path, then
the existing model scripts, with core and jQuery as required dependencies.

The current repository contains generated Compiler PHP but not the current
complete editable JCB definition package. The authoritative Compiler record,
its exact saved PHP Document source, target sharing, and self-build inputs have
not yet been verified. Do not hand-patch
`admin/src/View/Compiler/HtmlView.php`, infer paths from arbitrary authored PHP,
or claim that the Marked migration or JCB self-build has occurred.

The definition migration must preserve legacy target output if the same
definition serves Joomla 3–5. The scoped structured path input and any required
authoring/schema work must be reconciled with the actual definition source.

## Evidence to collect

Record the exact final commit, changed-source-to-generated-output mapping,
focused test results, Joomla 3–6 output comparisons, installed GUI/API run
links, and authoritative definition/self-build evidence. A public Hello World
golden run and an installed copy of the current repository do not prove a JCB
self-compilation.

Protected template changes ship with a same-change record under
`docs/graphical-user-interface-changes/`. Unavailable acceptance evidence is
reported explicitly; green unrelated checks do not replace it.
