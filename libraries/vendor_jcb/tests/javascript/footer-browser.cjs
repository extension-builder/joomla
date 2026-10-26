const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {createRequire} = require('node:module');
const {resolve} = require('node:path');
const {after, before, test} = require('node:test');

// Reuse the installed GUI suite's committed Playwright dependency lock.
const guiRequire = createRequire(resolve(__dirname, '../gui/package.json'));
const {chromium} = guiRequire('playwright');
const pages = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', [
	'-d', 'memory_limit=1G', resolve(__dirname, 'render-footer-pages.php'),
], {encoding: 'utf8', maxBuffer: 4 * 1024 * 1024}));
assert.deepEqual(Object.keys(pages), ['edit-view', 'list-view', 'edit-field', 'list-field', 'module-field']);
let browser;

before(async () => {
	browser = await chromium.launch({
		headless: true,
		executablePath: process.env.CHROMIUM_EXECUTABLE_PATH || undefined,
	});
});

after(async () => {
	await browser?.close();
});

for (const [name, fixture] of Object.entries(pages)) {
	test(`${name}: authored footer runs after its markup with unchanged globals and PHP values`, async () => {
		const page = await browser.newPage();
		const errors = [];
		page.on('pageerror', error => errors.push(error.message));
		try {
			await page.setContent(fixture.html, {waitUntil: 'load'});
			assert.deepEqual(errors, [], 'authored immediate DOM access must not throw');
			assert.equal(fixture.evaluations, 1, 'authored PHP must execute exactly once');
			const result = await page.evaluate(() => ({
				order: window.footerOrder,
				snapshot: window.footerSnapshot,
				after: window.footerAfter,
				callback: typeof window.authoredFooterAction,
				call: window.authoredFooterAction(),
			}));
			assert.deepEqual(result.order, ['head', 'body', 'footer', 'after', 'dom-ready']);
			assert.deepEqual(result.snapshot, {
				value: 'ready', state: 'loading', parent: 'BODY', payload: 'O\'Reilly "quoted" — Joomla',
			});
			assert.equal(result.callback, 'function', 'existing inline handlers need a global callback');
			assert.equal(result.after, 'ready:O\'Reilly "quoted" — Joomla');
			assert.equal(result.call, result.after);
		} finally {
			await page.close();
		}
	});
}
