// @ts-check
'use strict';

const { test, expect } = require('@playwright/test');
const { randomUUID } = require('node:crypto');
const { openView } = require('../helpers/jcb');
const { selectFancy, expectConditionalField } = require('../helpers/conditions');

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

const databaseRequirements = [
	'datatype', 'datalenght', 'datalenght_other', 'datadefault_other', 'indexes',
	'null_switch', 'store', 'on_get_model_field', 'on_save_model_field'
];

test('Field expert storage toggles both visible code requirements and their form metadata', async ({ page }) => {
	// A new, unsaved form avoids checkout and leaves every stored definition intact.
	await openView(page, 'field');
	await page.getByRole('tab', { name: 'Database', exact: true }).click();
	const fields = ['on_get_model_field', 'on_save_model_field'];
	await expect(page.locator('#jform_store')).toHaveValue('0');

	for (const name of fields) {
		await expectConditionalField(page, name, false, 'default storage does not require a custom code handler');
	}

	await selectFancy(page, 'store', 'Expert Mode - Custom');
	await expect(page.locator('#jform_store')).toHaveValue('6');
	for (const name of fields) {
		await expectConditionalField(page, name, true, 'expert storage exposes and requires each code handler');
	}

	await selectFancy(page, 'store', 'Default');
	await expect(page.locator('#jform_store')).toHaveValue('0');
	for (const name of fields) {
		await expectConditionalField(page, name, false, 'returning to default storage removes hidden requirements');
	}
});

for (const label of ['Note', 'Spacer']) {
	test(`Field ${label} waives database requirements and Text restores them after real defaults load`, async ({ page }) => {
		await openView(page, 'field');
		await chooseFieldType(page, label);
		await page.getByRole('tab', { name: 'Database', exact: true }).click();
		await expectNoDatabaseRequirements(page, label);
		await expect(page.locator('#jform_datatype'), `${label} has no database data type`).toHaveValue('');
		await expect(page.locator('#jform_fieldtype'), 'the selected field type remains required').toHaveJSProperty('required', true);

		await chooseFieldType(page, 'Text');
		await expectTextDefaults(page);
		for (const name of ['datatype', 'datalenght', 'indexes', 'null_switch', 'store']) {
			await expectConditionalField(page, name, true, 'Text restores its ordinary database requirements');
		}
		for (const name of ['datalenght_other', 'datadefault_other', 'on_get_model_field', 'on_save_model_field']) {
			await expectConditionalField(page, name, false, 'Text defaults leave conditional Other and Expert inputs optional');
		}

		await selectFancy(page, 'datalenght', 'Other');
		await expectConditionalField(page, 'datalenght_other', true, 'Other length requires its value');
		await selectFancy(page, 'datadefault', 'Other');
		await expectConditionalField(page, 'datadefault_other', true, 'Other default requires its value');
		await selectFancy(page, 'store', 'Expert Mode - Custom');
		await expectConditionalField(page, 'on_get_model_field', true, 'Expert storage requires its load handler');
		await expectConditionalField(page, 'on_save_model_field', true, 'Expert storage requires its save handler');

		await chooseFieldType(page, label);
		await expectNoDatabaseRequirements(page, label);
	});
}

test('Field keeps Text defaults when an earlier real Note response arrives late', async ({ page }) => {
	await openView(page, 'field');
	const note = await fieldTypeValue(page, 'Note');
	let releaseNote = () => {};
	let markNoteReceived = () => {};
	const heldNote = new Promise(resolve => { releaseNote = () => resolve(undefined); });
	const noteReceived = new Promise(resolve => { markNoteReceived = () => resolve(undefined); });

	// Delay delivery, not the endpoint: the response body still comes from Joomla.
	await page.route('**/index.php?**', async route => {
		const url = new URL(route.request().url());
		if (url.searchParams.get('task') !== 'ajax.fieldTypeProperties' || url.searchParams.get('id') !== note) {
			await route.continue();
			return;
		}
		const response = await route.fetch();
		markNoteReceived();
		await heldNote;
		await route.fulfill({ response });
	});

	try {
		const noteResponse = waitFieldTypeResponse(page, note);
		await selectFancy(page, 'fieldtype', 'Note');
		await noteReceived;
		await chooseFieldType(page, 'Spacer');
		await chooseFieldType(page, 'Text');
		await page.getByRole('tab', { name: 'Database', exact: true }).click();
		await expectTextDefaults(page);

		releaseNote();
		await expectFieldTypeResponse(await noteResponse);
		await expectTextDefaults(page);
		await expectConditionalField(page, 'datatype', true, 'a late Note response cannot hide Text database settings');
		await expectConditionalField(page, 'null_switch', true, 'a late response cannot remove the Text null requirement');
		await expectConditionalField(page, 'store', true, 'a late response cannot remove the Text storage requirement');
	} finally {
		releaseNote();
	}
});

/**
 * Read the installed catalogue's option identity from the real form.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} label
 * @returns {Promise<string>}
 */
async function fieldTypeValue(page, label) {
	const value = await page.locator('#jform_fieldtype option').filter({ hasText: new RegExp('^\\s*' + label + '\\s*$') }).getAttribute('value');
	if (!value) {
		throw new Error('The installed field type catalogue has no ' + label + ' option.');
	}
	return value;
}

/**
 * Wait for the actual properties endpoint for one selected field type.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} value
 * @returns {Promise<import('@playwright/test').Response>}
 */
function waitFieldTypeResponse(page, value) {
	return page.waitForResponse(response => {
		const url = new URL(response.url());
		return url.searchParams.get('task') === 'ajax.fieldTypeProperties' && url.searchParams.get('id') === value;
	});
}

/**
 * Require a successful Joomla response containing the generated property form.
 *
 * @param {import('@playwright/test').Response} response
 */
async function expectFieldTypeResponse(response) {
	expect(response.ok(), 'the installed field properties endpoint answers successfully').toBeTruthy();
	const properties = await response.json();
	expect(properties.subform, 'Joomla returns the selected field type property form').toEqual(expect.any(String));
	expect(properties.subform.length).toBeGreaterThan(0);
}

/**
 * Select through the visible widget and await its real server response.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} label
 */
async function chooseFieldType(page, label) {
	const value = await fieldTypeValue(page, label);
	const response = waitFieldTypeResponse(page, value);
	await selectFancy(page, 'fieldtype', label);
	await expectFieldTypeResponse(await response);
	await expect(page.locator('#jform_fieldtype')).toHaveValue(value);
	await expect(page.locator('#help'), 'field type help is rendered after the AJAX form arrives').toBeAttached();
}

/**
 * Assert every required database control is waived for a display-only field.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} label
 */
async function expectNoDatabaseRequirements(page, label) {
	for (const name of databaseRequirements) {
		await expectConditionalField(page, name, false, `${label} does not require hidden database setting ${name}`);
	}
}

/**
 * Await the standard Text catalogue defaults, including valid numeric zeroes.
 *
 * @param {import('@playwright/test').Page} page
 */
async function expectTextDefaults(page) {
	await expect(page.locator('#jform_fieldtype')).toHaveValue(await fieldTypeValue(page, 'Text'));
	await expect(page.locator('#jform_datatype'), 'Text database defaults have finished loading').toHaveValue('VARCHAR');
	await expect(page.locator('#jform_datalenght')).toHaveValue('255');
	await expect(page.locator('#jform_indexes'), 'zero is a valid default index choice').toHaveValue('0');
	await expect(page.locator('#jform_store'), 'zero is a valid default storage choice').toHaveValue('0');
}
