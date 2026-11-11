// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView } = require('../helpers/jcb');
const { selectFancy, expectConditionalField } = require('../helpers/conditions');

test('Dynamic get custom name follows the get method choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'dynamic_get');
	await page.getByRole('tab', { name: 'Main', exact: true }).click();
	await selectFancy(page, 'gettype', 'getItem');
	await expectConditionalField(page, 'getcustom', false, 'a custom get method requires a custom name');

	await selectFancy(page, 'gettype', 'getCustom');
	await expectConditionalField(page, 'getcustom', true, 'a custom get method requires a custom name');

	await selectFancy(page, 'gettype', 'getItem');
	await expectConditionalField(page, 'getcustom', false, 'a custom get method requires a custom name');
});
