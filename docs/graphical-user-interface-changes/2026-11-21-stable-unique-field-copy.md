# Stable compiler backport for unique fields in copied records

On 21 November 2026 the maintainer requested the complete fix for issue
#1328 from `main` for `stable-patch`. The compiler emits its copy helper
from these four templates. A change to the PHP emitters alone would leave
the unbounded lookup in generated administrator and frontend models.

## Modified compiler templates

| Repository path | Stable location | Generated target |
| --- | --- | --- |
| `admin/compiler/joomla_3/AdminModel.php` | `generateUnique()` | Joomla 3 administrator |
| `admin/compiler/joomla_3/AdminModel_site.php` | `generateUnique()` | Joomla 3 frontend edit |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php` | `generateUnique()` | Joomla 4, 5 and 6 administrator |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php` | `generateUnique()` | Joomla 4, 5 and 6 frontend edit |

Each helper uses the column settings produced by `UniqueFields`. A
conflicting numeric identifier now returns a localized model error. Text
suffixing respects available storage, GUID conflicts generate valid new
candidates, and a repeated match or the 100-attempt limit ends the search.
The save and batch emitters propagate a failed lookup before storing a
record. Batch operations retain their existing sequential behavior.

## Verification and delivery

The implementation and tests are ported from `d06a3fb0` and `f12f87a5`.
The regression suite exercises each distinct template against a controlled
table, including Save as Copy and Batch Copy. The stable compiler's version
catalogue selects the Joomla 4 template folder for Joomla 4, 5 and 6.
On PHP 8.4.27 with Joomla CMS 6.1.2, the four focused PHPUnit suites
passed 88 tests and 1,267 assertions. The issue-specific diff, PHP lint,
PHP contribution style, and test ownership checks passed. Browser copy
scenarios were not run as part of this issue-specific check. The change affects displayed model errors and generated
PHP. It introduces no markup, controls or keyboard interaction.

Recompile and reinstall affected extensions to deploy the new helpers.
Already-installed PHP and JCB's generated model helpers require regeneration.
Reverting the issue-specific change and rebuilding restores the prior
behavior. SQL UNIQUE constraints continue to protect concurrent writes.

## Authoritative JCB source reconciliation

| Repository path | Authoritative source | Transfer required | Status |
| --- | --- | --- | --- |
| `admin/compiler/joomla_3/AdminModel.php` | Core Joomla 3 administrator compiler template | Yes | Source identified |
| `admin/compiler/joomla_3/AdminModel_site.php` | Core Joomla 3 frontend edit compiler template | Yes | Source identified |
| `admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php` | Core shared modern administrator compiler template | Yes | Source identified |
| `admin/compiler/joomla_4/SITE_ADMIN_VIEW_MODEL.php` | Core shared modern frontend edit compiler template | Yes | Source identified |

The maintainer can transfer the four template changes with the emitted
metadata and callers, then verify the same scenarios after recompilation.
