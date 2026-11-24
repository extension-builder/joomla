const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {createRequire} = require('node:module');
const {resolve} = require('node:path');
const {after, before, test} = require('node:test');

// Execute actual compiler output and Joomla's head renderers before Chromium.
const guiRequire = createRequire(resolve(__dirname, '../gui/package.json'));
const {chromium} = guiRequire('playwright');
const pages = JSON.parse(execFileSync(process.env.PHP_BINARY || 'php', [
	'-d', 'memory_limit=1G', resolve(__dirname, 'render-view-assets-pages.php'),
], {encoding: 'utf8', maxBuffer: 4 * 1024 * 1024}));
assert.equal(Object.keys(pages).length, 12, 'all four templates need enabled, explicit and empty-input cases');
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
	test(`${name}: rendered assets execute in dependency order before the original inline receiver`, async () => {
		const page = await browser.newPage();
		const errors = [];
		const requests = [];
		page.on('pageerror', error => errors.push(error.message));
		const payloads = new Map([
			['https://example.test/subdir/core.js', '(window.viewAssetOrder ||= []).push("core"); window.fixtureCore = true;'],
			['https://example.test/subdir/jquery.js', '(window.viewAssetOrder ||= []).push("jquery"); window.fixtureJquery = true;'],
			['https://example.test/subdir/jquery-noconflict.js', 'window.viewAssetOrder.push("jquery-noconflict");'],
			[`https://example.test/subdir/media/com_fixture/js/${fixture.client}/article.js`, fixture.script],
			["https://example.test/subdir/media/com_fixture/js/extra's.js", 'window.viewAssetOrder.push("extra"); window.fixtureFormat = value => "<strong>" + value + "</strong>";'],
			['https://cdn.example.test/library.js', 'window.viewAssetOrder.push("protocol"); window.fixtureProtocol = true;'],
			['https://example.test/subdir/media/com_fixture/js/model.js', 'window.viewAssetOrder.push("model"); window.fixtureModel = "loaded";'],
		]);
		await page.route('**/*', async route => {
			const url = new URL(route.request().url());
			const key = url.origin + decodeURIComponent(url.pathname);
			if (key === 'https://example.test/subdir/assets') {
				await route.fulfill({contentType: 'text/html', body: fixture.html});
			} else if (key === 'https://example.test/subdir/media/com_fixture/css/view.css') {
				await route.fulfill({contentType: 'text/css', body: '#asset-result { color: rgb(20, 80, 120); }'});
			} else if (payloads.has(key)) {
				requests.push({key, flavour: url.searchParams.get('flavour')});
				await route.fulfill({contentType: 'text/javascript', body: payloads.get(key)});
			} else {
				errors.push(`Unexpected asset request: ${url.href}`);
				await route.abort();
			}
		});
		try {
			await page.goto('https://example.test/subdir/assets', {waitUntil: 'load'});
			assert.deepEqual(errors, [], 'the complete generated block must execute without browser errors');
			const result = await page.evaluate(() => ({
				order: window.viewAssetOrder,
				prerequisites: window.fixturePrerequisites,
				viewRendered: window.fixtureViewRendered,
				inline: window.fixtureInline,
				rendered: window.fixtureRendered,
				color: getComputedStyle(document.getElementById('asset-result')).color,
				border: getComputedStyle(document.getElementById('asset-result')).borderTopWidth,
			}));
			assert.deepEqual(result.order.filter(item => !['core', 'jquery', 'jquery-noconflict'].includes(item)),
				fixture.populated ? ['extra', 'protocol', 'view', 'model', 'inline'] : ['model', 'inline']);
			assert.equal(result.inline, 'loaded', 'the authored inline consumer runs after the model script');
			assert.equal(result.color, 'rgb(20, 80, 120)', 'model styles still resolve under the site subdirectory');
			assert.equal(result.border, '3px', 'the authored inline style still follows the style phase');
			assert.equal(result.order.includes('jquery-noconflict'), fixture.jquery === 1,
				'the automatic jQuery setting retains its no-conflict activation');
			if (fixture.populated) {
				assert.deepEqual(result.prerequisites, [true, true, true], 'core, explicit jQuery and configured prerequisites precede the generated view file');
				assert.equal(result.viewRendered, '<strong>view</strong>', 'the generated view file consumes its prerequisite during execution');
				assert.equal(result.rendered, '<strong>ready</strong>', 'the unchanged inline receiver sees the view-local library');
				const extraRequests = requests.filter(request => request.key.endsWith("/extra's.js"));
				assert.equal(extraRequests.length, 1, 'repeated normalized paths activate one script');
				assert.equal(extraRequests[0].flavour, 'a&b', 'escaped path literals retain their query values');
				assert.ok(requests.some(request => request.key === 'https://cdn.example.test/library.js'),
					'protocol-relative paths keep their remote origin');
			} else {
				assert.deepEqual(result.order, ['core', 'model', 'inline'], 'empty inputs add no dummy script or jQuery dependency');
				assert.equal(requests.length, 2);
			}
		} finally {
			await page.close();
		}
	});
}
