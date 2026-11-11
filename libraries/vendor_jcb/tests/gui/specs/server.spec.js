// @ts-check
'use strict';

const { test } = require('@playwright/test');
const { openView } = require('../helpers/jcb');
const { selectFancy, expectConditionalField } = require('../helpers/conditions');

test('Server host follows the transfer protocol choice', async ({ page }) => {
	// Exercise a new form only; the installed model is covered natively without saving.
	await openView(page, 'server');
	await page.getByRole('tab', { name: 'Details', exact: true }).click();
	await selectFancy(page, 'protocol', 'FTP');
	await expectConditionalField(page, 'host', false, 'SFTP requires a host while FTP uses its signature');

	await selectFancy(page, 'protocol', 'SFTP');
	await expectConditionalField(page, 'host', true, 'SFTP requires a host while FTP uses its signature');

	await selectFancy(page, 'protocol', 'FTP');
	await expectConditionalField(page, 'host', false, 'SFTP requires a host while FTP uses its signature');
});
