// @ts-check
'use strict';

/**
 * Small helpers every JCB GUI spec shares.
 *
 * Selector policy: prefer what a person sees — roles and the natural-language
 * strings the views print through Text::_() — and fall back to the stable ids
 * the views define. Never select on generated markup that a template change
 * would rename silently.
 */

/**
 * Open one view of the component in the administrator.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} view  The view name, e.g. 'extrusion'.
 * @param {string} extra Extra query parts, '&'-prefixed, optional.
 */
async function openView(page, view, extra = '') {
	await page.goto(
		'/administrator/index.php?option=com_componentbuilder&view=' + view + extra
	);
}

/**
 * Set one of the btn-group radio fields the JCB views build dynamically.
 *
 * Joomla renders these radios label-over-input, so the input itself is not
 * clickable; the label carrying its id is.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} name   The field name.
 * @param {string} value  The option value to select.
 */
async function setRadio(page, name, value) {
	const input = page.locator(
		'input[type="radio"][name="' + name + '"][value="' + value + '"]'
	);
	const id = await input.getAttribute('id');

	if (id) {
		await page.locator('label[for="' + id + '"]').click();
	} else {
		await input.check({ force: true });
	}

	await input.isChecked();
}

module.exports = { openView, setRadio };
