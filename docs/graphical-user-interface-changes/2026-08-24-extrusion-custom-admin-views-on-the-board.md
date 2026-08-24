# Extrusion pairing board: custom admin views

## Change identity

- **Date first changed:** 2026-08-24
- **Author/implementer:** Claude (coding agent), for lemuel@vdm.to
- **Task/issue/PR:** Branch `claude/joomla-extrusion-true-shapes`, follow-up
  to PR #39 (extrusion data quality)
- **Change record status:** Ready for review

## Explicit permission

- **Permission reference:** Active session review of a live extrusion run:
  the component's custom admin views (the administrator screens outside its
  tables) were neither created nor shown, fields that already stand in JCB
  were recreated instead of matched and reused, and language constants were
  stored instead of the English they stand for. The owner asked for a deep
  reverse-engineering of how JCB itself stores these structures and for the
  extrusion to land everything linked to its correct places. Standing
  permission to branch, commit in the owner's name, and open a pull request.
- **Authorized paths:** The extrusion view's own `admin/**` assets and
  template, alongside the engine work in `libraries/vendor_jcb/**`.
- **Authorized outcome:** The pairing board shows every kind the harvest
  recovers — custom admin views included — and matched candidates propose
  reuse, so the import relates everything instead of twinning it.
- **Permission summary:** Covers the extrusion board's candidate kinds and
  their text prints only. No other admin view, no compiler templates.

## Purpose and rationale

The engine now recovers a component's custom admin views (administrator
templates no table view answers for) and pairs every harvested field against
the whole field table JCB already holds. The board rendered neither: it knew
only admin views, site views, layouts, templates and powers, so a custom
admin view travelled to the import invisible, and its match could not be
inspected or overruled. The board must show what the import will do — that
is the whole point of the pairing step.

## Affected paths

### Created

- `docs/graphical-user-interface-changes/2026-08-24-extrusion-custom-admin-views-on-the-board.md`

### Modified

- `admin/assets/js/extrusion.js`
- `admin/tmpl/extrusion/default.php`

### Moved or renamed

- None

### Deleted

- None

## Implementation details

### `admin/assets/js/extrusion.js`

- **Change type:** Modified
- **Stable location(s):** `matchByGuid()`, `matchByName()`, `match()`,
  `rematch()`, `proposal()`, `counts()`, `openModal()`, `renderBoard()`,
  `allCandidates()`, `modalPool()`
- **What changed:** The `custom_admin_view` kind joined every place the
  board enumerates kinds: it re-matches against the served
  `catalogue.custom_admin_views` pool, renders as its own section between
  site views and layouts, travels with bulk work and the import decisions,
  and offers the same target picker pool as every other kind. The engine
  only serves candidates no table view answers for, so the section holds
  the component's real custom screens, never its table views.

  Matching now speaks identity the way JCB does: a candidate whose guid
  already stands in the catalogue matches **by guid** and proposes an
  update -- it IS that definition. A field answering to one the paired
  view **already links** is that view's own wiring rediscovered and
  weighs the same. A candidate that merely shares a name with something
  elsewhere matches **by name**: the section counts it as *similar*, the
  default stays *create new*, and the Update modal opens pre-searched on
  the lookalike so reusing it is one confirmation away. The counts line
  reads `(N items, G matched, S similar, K new)`.
- **Why:** Everything in JCB is linked by guid, so only a guid in common
  is the same definition; forcing an update onto a name lookalike would
  misstate identity, and hiding it would recreate what may well be meant
  for reuse. The person decides -- with the evidence in view.
- **Related paths/symbols:** `Extrusion\Resolver\Candidates` (guid-first
  matching, filtered custom candidates), `Extrusion\Resolver\Reuse`
  (server-side defaults, guid matches only),
  `Extrusion\Writer\CustomAdminView` (refuses table views' templates).

### `admin/tmpl/extrusion/default.php`

- **Change type:** Modified
- **Stable location(s):** the `window.JCBExtrusion.text` map
- **What changed:** Two new natural-language prints:
  `customAdminViews: Text::_('Custom admin views', true)` naming the new
  board section, and `similar: Text::_('similar', true)` naming the
  name-resemblance count.
- **Why:** Every visible string on this view is a natural-language
  `Text::_()` print, per the documented convention.
- **Related paths/symbols:** `docs/development/user-interface-language-strings.md`.

## GUI test coverage

- `libraries/vendor_jcb/tests/gui/specs/extrusion.spec.js` — the component
  journey now keeps the language scope ON (the central-catalogue discovery
  under test resolves the labels), asserts the custom admin view section
  stands on the board with rows in it, asserts that section never offers
  any table view of the same run (the custom/admin separation, seen from
  the browser), and asserts at least one view's fields are counted similar
  to fields JCB already holds — offered for reuse, never forced onto them.

## Rollback

Revert the two modified files and this record; the engine work in
`libraries/vendor_jcb/**` stands on its own and is covered by its own unit
tests.
