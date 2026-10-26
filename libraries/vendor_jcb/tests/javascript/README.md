# Generated JavaScript regressions

The dependency-free DKIM checks run with:

```bash
node --test libraries/vendor_jcb/tests/javascript/*.test.cjs
```

The authored-footer regression additionally uses the prepared PHP test runtime
and Chromium from the existing GUI suite's locked Playwright dependencies:

```bash
npm ci --prefix libraries/vendor_jcb/tests/gui --no-audit --no-fund
libraries/vendor_jcb/tests/gui/node_modules/.bin/playwright install chromium
node --test libraries/vendor_jcb/tests/javascript/footer-browser.cjs
```

CI runs this browser check in the PHP 8.4 unit-workflow job after installing
Chromium's system dependencies. `PHP_BINARY` may select a PHP executable and
`CHROMIUM_EXECUTABLE_PATH` may select an existing Chromium installation locally.

The PHP fixture follows real view/field collection through the custom-code
dispenser and Joomla 6 edit-view, list-view and module emitters. It evaluates
the generated PHP, then renders the real Joomla document's Web Asset Manager
scripts into the head. Chromium loads that complete document and verifies that
authored footer code can access existing controls immediately, retains global
callbacks and script ordering, executes before DOMContentLoaded, and receives
its PHP-generated values exactly once. No browser, DOM or asset-head renderer is
mocked. Unrelated custom-code processors and the application boundary are
isolated through the existing architecture test support.

These five browser cases protect the generated footer contract. They do not
replace full maintained-definition compilation or installation in Joomla.
