// @ts-check
'use strict';

const { test, expect } = require('@playwright/test');
const { openView } = require('../helpers/jcb');

test('Compiler identifies its page and keeps all supported target versions selectable', async ({ page }) => {
	await openView(page, 'compiler');
	await expect(page.getByRole('heading', { name: 'Compiler', exact: true }),
		'the imported compiler page supplies its toolbar title').toBeVisible();
	await expect(page.getByRole('heading', { name: 'Ready to compile a component', exact: true })).toBeVisible();
	await expect(page.locator('#joomla_version option')).toHaveText(['Joomla 3', 'Joomla 4', 'Joomla 5', 'Joomla 6']);
	for (const version of ['3', '4', '5', '6']) {
		await page.locator('#joomla_version').selectOption(version);
		await expect(page.locator('#joomla_version')).toHaveValue(version);
	}
});

test('Compiler explains a missing component without submitting a build', async ({ page }) => {
	await openView(page, 'compiler');
	await page.locator('#component_id').selectOption('');
	await page.getByRole('button', { name: 'Compile Component', exact: true }).click();
	await expect(page.getByText('You must select a component!', { exact: true }),
		'an empty selection gives the operator an actionable validation message').toBeVisible();
	await expect(page.locator('#compilerForm')).toBeVisible();
	await expect(page.locator('#compiler')).toBeHidden();
	await expect(page.locator('#component_id')).toHaveValue('');
});
