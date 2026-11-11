// @ts-check
'use strict';

const { expect } = require('@playwright/test');

/**
 * Select a visible option in Joomla's fancy select without bypassing its events.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} name The stable Joomla field name.
 * @param {string} label The option label shown to the user.
 */
async function selectFancy(page, name, label) {
	const control = page.locator('joomla-field-fancy-select').filter({ has: page.locator('#jform_' + name) });
	await control.getByRole('combobox').click();
	await control.getByRole('option', { name: label, exact: true }).click();
}

/**
 * Assert the visible group and the metadata Joomla uses for required fields.
 * Radio fieldsets have no native HTML required property; their class and ARIA
 * state still participate in Joomla's form validation and accessible feedback.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} name The stable Joomla field name.
 * @param {boolean} required Whether this condition exposes a required field.
 * @param {string} message The user-visible promise under test.
 */
async function expectConditionalField(page, name, required, message) {
	const field = page.locator('#jform_' + name);
	const group = page.locator('.control-group').filter({ has: field });
	if (required) {
		await expect(group, message).toBeVisible();
		await expect(field, message).toHaveClass(/\brequired\b/);
		await expect(field, message).toHaveAttribute('aria-required', 'true');
	} else {
		await expect(group, message).toBeHidden();
		await expect(field, message).not.toHaveClass(/\brequired\b/);
		await expect(field, message).not.toHaveAttribute('aria-required');
	}
	if (await field.evaluate(element => ['INPUT', 'SELECT', 'TEXTAREA'].includes(element.tagName))) {
		await expect(field, message).toHaveJSProperty('required', required);
	}
}

module.exports = { selectFancy, expectConditionalField };
