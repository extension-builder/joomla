// @ts-check
'use strict';

const { test, expect } = require('@playwright/test');
const { randomUUID } = require('node:crypto');
const { openView } = require('../helpers/jcb');

/** Open only the fields owned by one test, including any row left in the trash. */
async function ownedFields(page, name) {
	await openView(page, 'fields', '&' + new URLSearchParams({
		'filter[search]': name,
		'filter[published]': '*',
		'filter[category_id]': '',
		'filter[access]': '',
		'filter[fieldtype]': '',
		'filter[datatype]': '',
		'filter[indexes]': '',
		'filter[null_switch]': '',
		'filter[store]': '',
		'list[limit]': '20',
		'list[fullordering]': 'a.id DESC',
		limitstart: '0'
	}));
	await expect(page.locator('#filter_search'), 'the owned-row lookup reached the real Fields list').toHaveValue(name);
	return page.locator('#fieldList tbody a[href*="task=field.edit"]').filter({ hasText: name });
}

/** Exercise the actual Joomla controller for cleanup, with its normal CSRF check. */
async function fieldTask(page, task, id) {
	const token = await page.locator('#adminForm input[type="hidden"]').evaluateAll((inputs) => {
		const input = inputs.find((element) => /^[a-f0-9]{32}$/.test(element.getAttribute('name') || ''));
		return input && input.getAttribute('name');
	});
	expect(token, 'the installed Fields form supplies a CSRF token for fixture cleanup').toBeTruthy();
	const response = await page.request.post('/administrator/index.php?option=com_componentbuilder', {
		form: { task, 'cid[]': id, [token]: '1' }
	});
	expect(response.ok(), `${task} completes for the field owned by this test`).toBe(true);
}

// Saving is the behaviour under test and has no dry-run option. Keep teardown
// outside the test's timeout so even a failed save/reopen assertion removes only
// this test's uniquely named rows through Joomla's check-in/trash/delete path.
const saveTest = test.extend({
	fieldName: [async ({ page }, use) => {
		const name = 'JCB GUI Field ' + randomUUID().replaceAll('-', '').slice(0, 12);
		await use(name);
		const links = await ownedFields(page, name);
		const hrefs = await links.evaluateAll((elements) => elements.map((element) => element.getAttribute('href')));
		for (const href of hrefs) {
			const id = new URL(href, page.url()).searchParams.get('id');
			expect(id, 'fixture cleanup addresses an observed field identity').toMatch(/^[1-9][0-9]*$/);
			await fieldTask(page, 'fields.checkin', id);
			await fieldTask(page, 'fields.trash', id);
			await fieldTask(page, 'fields.delete', id);
		}
		await expect(await ownedFields(page, name), 'saving a field leaves no test definitions behind').toHaveCount(0);
	}, { timeout: 60_000 }]
});

/** Pick the real field type and wait until its AJAX-loaded properties are shown. */
async function selectFieldType(page, name) {
	await page.getByRole('tab', { name: 'Set Properties', exact: true }).click();
	const type = page.locator('joomla-field-fancy-select').filter({ has: page.locator('#jform_fieldtype') });
	await type.getByRole('combobox').click();
	const response = page.waitForResponse((result) => new URL(result.url()).searchParams.get('task') === 'ajax.fieldTypeProperties');
	await type.getByRole('option', { name, exact: true }).click();
	expect((await response).ok(), `${name} properties are supplied by the installed JCB`).toBe(true);
	await expect(page.locator('#properties_properties0__value'), `${name} property controls finish loading`).toBeVisible();
	await expect(page.locator('#properties_properties0__value'), 'the properties belong to the newly selected type').toHaveValue(name.toLowerCase());
}

async function expectNoDatabase(page, name) {
	await page.getByRole('tab', { name: 'Database', exact: true }).click();
	const notice = page.locator('.note_no_database_settings_needed');
	await expect(notice, `${name} displays its database configuration notice`).toBeVisible();
	await expect(notice, `${name} explains why no database configuration is needed`)
		.toContainText('This field does not require any database settings to function correctly.');
	await expect(page.locator('#jform_datatype'), `${name} keeps its database type empty`).toHaveValue('');
	for (const field of ['datatype', 'null_switch', 'store']) {
		await expect(page.locator('#jform_' + field), `${name} hides the unused ${field} control`).toBeHidden();
		await expect(page.locator('#jform_' + field), `${name} removes the browser requirement for ${field}`).not.toHaveAttribute('required');
	}
}

async function saveAndClose(page, name) {
	await page.getByRole('button', { name: 'Save & Close', exact: true }).click();
	await expect(page, `${name} passes server validation and returns to the Fields list`).toHaveURL(/[?&]view=fields(?:&|$)/);
	await ownedFields(page, name);
	await expect(page.getByRole('link', { name, exact: true }), `${name} was persisted by the save`).toBeVisible();
}

for (const type of ['Note', 'Spacer']) {
	saveTest(`${type} saves new and existing fields without a database type`, async ({ page, fieldName }) => {
		await openView(page, 'field');
		await page.locator('#jform_name').fill(fieldName);
		await selectFieldType(page, type);
		await expect(page.locator('#properties_properties1__name'), 'the field XML has its mandatory name property').toHaveValue('name');
		const xmlName = fieldName.toLowerCase().replaceAll(' ', '_');
		await page.locator('#properties_properties1__value').fill(xmlName);
		await expectNoDatabase(page, type);
		await saveAndClose(page, fieldName);

		await page.getByRole('link', { name: fieldName, exact: true }).click();
		await expect(page.locator('#jform_name')).toHaveValue(fieldName);
		await expect(page.locator('#properties_properties0__value'), `${type} reloads its saved properties`).toHaveValue(type.toLowerCase());
		await expect(page.locator('#properties_properties1__value'), `${type} preserves its saved XML name`).toHaveValue(xmlName);
		await expectNoDatabase(page, type);
		const updatedName = fieldName + ' edited';
		await page.getByRole('tab', { name: 'Set Properties', exact: true }).click();
		await page.locator('#jform_name').fill(updatedName);
		await saveAndClose(page, updatedName);
	});
}

test('Changing a Note to a Text field restores the database type requirement', async ({ page }) => {
	await openView(page, 'field');
	await selectFieldType(page, 'Note');
	await expectNoDatabase(page, 'Note');
	await selectFieldType(page, 'Text');
	await page.getByRole('tab', { name: 'Database', exact: true }).click();
	await expect(page.locator('#jform_datatype-lbl'), 'a database field exposes its Data Type setting').toBeVisible();
	await expect(page.locator('#jform_datatype'), 'a database field still requires Data Type').toHaveJSProperty('required', true);
});

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
