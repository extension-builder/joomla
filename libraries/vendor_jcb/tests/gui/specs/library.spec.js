// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');
const { expectConditionalField } = require('../helpers/conditions');

test('Library behavior follows the destination folder choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'library');
	await page.getByRole('tab', { name: 'Behaviour', exact: true }).click();
	await setRadio(page, 'jform[target]', '2');
	await expectConditionalField(page, 'type', false, 'media libraries expose their type behavior while the Libraries folder does not');

	await setRadio(page, 'jform[target]', '1');
	await expectConditionalField(page, 'type', true, 'media libraries expose their type behavior while the Libraries folder does not');

	await setRadio(page, 'jform[target]', '2');
	await expectConditionalField(page, 'type', false, 'media libraries expose their type behavior while the Libraries folder does not');
});
