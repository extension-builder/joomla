// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');
const { expectConditionalField } = require('../helpers/conditions');

test('Custom code function name follows the manual target choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'custom_code');
	await page.getByRole('tab', { name: 'Details', exact: true }).click();
	await setRadio(page, 'jform[target]', '1');
	await expectConditionalField(page, 'function_name', false, 'manual custom code needs a function name and automation does not');

	await setRadio(page, 'jform[target]', '2');
	await expectConditionalField(page, 'function_name', true, 'manual custom code needs a function name and automation does not');

	await setRadio(page, 'jform[target]', '1');
	await expectConditionalField(page, 'function_name', false, 'manual custom code needs a function name and automation does not');
});
