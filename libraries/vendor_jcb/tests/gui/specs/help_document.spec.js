// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView } = require('../helpers/jcb');
const { selectFancy, expectConditionalField } = require('../helpers/conditions');

test('Help document URL follows the content type choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'help_document');
	await page.getByRole('tab', { name: 'Details', exact: true }).click();
	await selectFancy(page, 'type', 'Text');
	await expectConditionalField(page, 'url', false, 'URL help requires a URL while text help does not');

	await selectFancy(page, 'type', 'URL');
	await expectConditionalField(page, 'url', true, 'URL help requires a URL while text help does not');

	await selectFancy(page, 'type', 'Text');
	await expectConditionalField(page, 'url', false, 'URL help requires a URL while text help does not');
});
