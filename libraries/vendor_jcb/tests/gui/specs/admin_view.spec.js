// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');
const { expectConditionalField } = require('../helpers/conditions');

test('Admin view MySQL source follows the install SQL choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'admin_view');
	await page.getByRole('tab', { name: 'MySQL', exact: true }).click();
	await setRadio(page, 'jform[add_sql]', '0');
	await expectConditionalField(page, 'source', false, 'install SQL needs a MySQL source only when enabled');

	await setRadio(page, 'jform[add_sql]', '1');
	await expectConditionalField(page, 'source', true, 'install SQL needs a MySQL source only when enabled');

	await setRadio(page, 'jform[add_sql]', '0');
	await expectConditionalField(page, 'source', false, 'install SQL needs a MySQL source only when enabled');
});
