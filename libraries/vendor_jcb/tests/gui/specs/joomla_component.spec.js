// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');
const { expectConditionalField } = require('../helpers/conditions');

test('Component update server target follows the update server choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'joomla_component');
	await page.getByRole('tab', { name: 'Dynamic Integration', exact: true }).click();
	await setRadio(page, 'jform[add_update_server]', '0');
	await expectConditionalField(page, 'update_server_target', false, 'the update server target is required only while update servers are enabled');

	await setRadio(page, 'jform[add_update_server]', '1');
	await expectConditionalField(page, 'update_server_target', true, 'the update server target is required only while update servers are enabled');

	await setRadio(page, 'jform[add_update_server]', '0');
	await expectConditionalField(page, 'update_server_target', false, 'the update server target is required only while update servers are enabled');
});
