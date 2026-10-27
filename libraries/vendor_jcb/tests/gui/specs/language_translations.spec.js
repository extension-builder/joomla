// @ts-check
'use strict';

const { test, expect } = require('@playwright/test');
const { openView } = require('../helpers/jcb');
const fixturePath = process.env.JCB_LANGUAGE_TRANSLATION_FIXTURES;
if (!fixturePath) {
	throw new Error('Run the disposable GUI harness to seed language translation fixtures.');
}
const fixtures = JSON.parse(require('node:fs').readFileSync(fixturePath, 'utf8'));

/** Submit the real Search Tools control and wait for the server-rendered page. */
async function select(page, name, value) {
	const field = page.locator('#filter_' + name);
	if (!(await field.isVisible())) {
		await page.getByRole('button', { name: 'Filter Options', exact: true }).click();
	}
	await Promise.all([page.waitForNavigation(), field.selectOption(value)]);
	await expect(field).toHaveValue(value);
}

/** Compare actual source links, so empty, extra and missing result rows all fail. */
async function sources(page, names) {
	const links = page.locator('#language_translationList tbody a[href*="task=language_translation.edit"]');
	await expect(links).toHaveCount(names.length);
	expect((await links.allTextContents()).map((value) => value.trim()).sort()).toEqual(
		names.map((name) => fixtures.sources[name]).sort()
	);
}

test.describe('Language Translations filters', () => {
	test.beforeEach(async ({ page }) => {
		await openView(page, 'language_translations', '&' + new URLSearchParams({
			'filter[search]': fixtures.prefix,
			'filter[extension]': '',
			'filter[translated]': '',
			'filter[not_translated]': '',
			'list[limit]': '20',
			'list[fullordering]': 'a.id ASC',
			limitstart: '0'
		}));
		await sources(page, Object.keys(fixtures.sources));
	});

	test('matches exact nonblank entries and combines the extension filter', async ({ page }) => {
		await select(page, 'translated', fixtures.language_a);
		await sources(page, ['zero', 'translated', 'complete', 'other_extension']);
		await select(page, 'extension', fixtures.extension_a);
		await sources(page, ['zero', 'translated', 'complete']);
		await select(page, 'not_translated', fixtures.language_b);
		await sources(page, ['zero', 'translated']);
		await select(page, 'extension', fixtures.extension_b);
		await sources(page, ['other_extension']);
	});

	test('finds compiler-created and legacy null rows before manual save and keeps all/none distinct', async ({ page }) => {
		await select(page, 'not_translated', fixtures.language_a);
		await sources(page, ['missing', 'empty', 'blank', 'incidental', 'compiler_created']);
		await select(page, 'not_translated', 'none');
		await sources(page, ['missing', 'empty', 'blank', 'compiler_created']);
		await select(page, 'not_translated', '');
		await select(page, 'translated', 'all');
		await sources(page, ['complete']);
		await select(page, 'translated', '');
		await sources(page, Object.keys(fixtures.sources));
		await select(page, 'translated', fixtures.language_a);
		await select(page, 'not_translated', fixtures.language_a);
		await sources(page, []);
		await expect(page.getByText('No Matching Results', { exact: true })).toBeVisible();
	});

	test('paginates and clamps the page when the selected result set becomes smaller', async ({ page }) => {
		await Promise.all([page.waitForNavigation(), page.locator('#list_limit').selectOption('5')]);
		await sources(page, ['missing', 'empty', 'blank', 'incidental', 'zero']);
		await Promise.all([
			page.waitForNavigation(),
			page.getByRole('link', { name: 'Go to next page', exact: true }).click()
		]);
		await sources(page, ['translated', 'complete', 'other_extension', 'compiler_created']);
		await select(page, 'translated', fixtures.language_a);
		await sources(page, ['zero', 'translated', 'complete', 'other_extension']);
	});
});
