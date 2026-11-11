// @ts-check
'use strict';

const { test, expect } = require('@playwright/test');
const { openView } = require('../helpers/jcb');
const { selectFancy, expectConditionalField } = require('../helpers/conditions');

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
