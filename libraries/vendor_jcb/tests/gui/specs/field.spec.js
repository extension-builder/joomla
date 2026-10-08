// @ts-check
'use strict';

const { test, expect } = require('@playwright/test');
const { openView } = require('../helpers/jcb');

test('Field expert storage toggles both visible code requirements and their form metadata', async ({ page }) => {
	// A new, unsaved form avoids checkout and leaves every stored definition intact.
	await openView(page, 'field');
	await page.getByRole('tab', { name: 'Database', exact: true }).click();
	const store = page.locator('joomla-field-fancy-select').filter({ has: page.locator('#jform_store') });
	const fields = ['on_get_model_field', 'on_save_model_field'];
	const notRequired = page.locator('#jform_not_required');
	await expect(page.locator('#jform_store')).toHaveValue('0');

	for (const name of fields) {
		await expect(page.locator('#jform_' + name)).toBeHidden();
		await expect(page.locator('#jform_' + name)).not.toHaveAttribute('required');
		await expect.poll(async () => (await notRequired.inputValue()).split(',')).toContain(name);
	}

	await store.getByRole('combobox').click();
	await store.getByRole('option', { name: 'Expert Mode - Custom', exact: true }).click();
	await expect(page.locator('#jform_store')).toHaveValue('6');
	for (const name of fields) {
		await expect(page.locator('#jform_' + name), 'expert storage exposes each code handler').toBeVisible();
		await expect(page.locator('#jform_' + name)).toHaveJSProperty('required', true);
		await expect(page.locator('#jform_' + name)).toHaveAttribute('aria-required', 'true');
		await expect.poll(async () => (await notRequired.inputValue()).split(',')).not.toContain(name);
	}

	await store.getByRole('combobox').click();
	await store.getByRole('option', { name: 'Default', exact: true }).click();
	await expect(page.locator('#jform_store')).toHaveValue('0');
	for (const name of fields) {
		await expect(page.locator('#jform_' + name), 'returning to default storage removes hidden requirements').toBeHidden();
		await expect(page.locator('#jform_' + name)).toHaveJSProperty('required', false);
		await expect(page.locator('#jform_' + name)).not.toHaveAttribute('aria-required');
		await expect.poll(async () => (await notRequired.inputValue()).split(',')).toContain(name);
	}
});
