// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');
const { expectConditionalField } = require('../helpers/conditions');

test('Fieldtype database defaults expose and require their data type', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'fieldtype');
	await page.getByRole('tab', { name: 'Database (defaults)', exact: true }).click();
	await setRadio(page, 'jform[has_defaults]', '0');
	await expectConditionalField(page, 'datatype', false, 'database defaults need a data type only when enabled');

	await setRadio(page, 'jform[has_defaults]', '1');
	await expectConditionalField(page, 'datatype', true, 'database defaults need a data type only when enabled');

	await setRadio(page, 'jform[has_defaults]', '0');
	await expectConditionalField(page, 'datatype', false, 'database defaults need a data type only when enabled');
});
