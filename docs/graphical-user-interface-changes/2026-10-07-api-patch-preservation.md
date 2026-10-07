# Preserve omitted fields in generated API PATCH requests

## Change identity and permission

- Date: 2026-10-07; implementer: Codex; PR: #58.
- Status: Implemented; installed verification in progress on PR #58.
- The active request explicitly approves the API compiler preservation plan,
  including generated controller/model code, for the stable 6.2.0 rollout.
- Authorized protected path: `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php`.

## Purpose and affected paths

Modified: `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php`, item controller
`preprocessSaveData()`. No protected files are created, moved, or deleted.

Joomla merges raw stored columns into PATCH data. The generated model expects
the input representation returned by its `getItem()` method, including decoded
code, JSON/subforms, encryption and expert field transformations. The API
template needs a native preprocessing hook before Joomla validates that data.

Only omitted table columns are hydrated from the model. Explicit values remain
input, including empty values and null. Omitted tags are hydrated from the
model's TagsHelper. Failed reads abort before saving. Normal create and
administrator submissions retain their existing behavior. Missing-value save
defaults are guarded in the shared compiler generators.

The generated save method snapshots omitted raw columns, skips their storage
transformations, and restores their exact bytes. Custom before-save derivations
and expert-code mutations remain authoritative; explicit input and save-as-copy
follow the normal transforms. This avoids lossy JSON round trips and unnecessary
re-encryption. ACL-filtered columns are never reintroduced. Failed raw reads
abort the update.

## Impact and verification

There is no visual or accessibility change. Generated Joomla 4/5/6 item API
controllers gain the hook; Joomla 3 has no generated API area. The normal
authorization, form filtering/validation and save lifecycle remains in place.

The real Joomla API controller/form lifecycle regression passes 12 tests and
126 assertions. Generated save-code regressions pass 27 tests and 91 assertions,
including JSON scalars/null, Base64, ciphertext, custom derivations, expert
mutations, ACL filtering, failed reads, and normal create/administrator behavior.
The installed fixture exercises repeated ID/GUID updates, exact storage bytes,
decoded readback, explicit replacement and clearing on both Demo resource names.
Its hosted result is recorded in the PR; local execution requires Joomla/MySQL.
No full JCB self-compilation result is claimed.
GUI spec files: none, because this template changes API request preparation and
does not alter a graphical view. Its executable coverage is in the compiler
API test suite. Hosted GUI and compiler golden jobs remain applicable gates.

## Reconciliation and rollback

| Repository path | Authoritative source | Transfer required | Status | Next action |
| --- | --- | --- | --- | --- |
| `admin/compiler/joomla_4/API_VIEW_CONTROLLER.php` | JCB maintained compiler template of the same path | Yes | Source identified | Maintainer carries this template into the maintained JCB build and verifies regenerated item API controllers. |

Rollback: revert this API preservation commit together with its compiler
generators, tests and record. Do not remove the hook independently of its tests.
