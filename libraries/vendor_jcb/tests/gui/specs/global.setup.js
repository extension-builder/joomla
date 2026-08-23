// @ts-check
'use strict';

/**
 * Log into the Joomla administrator once and keep the session.
 *
 * Every spec project depends on this one and reuses the saved storage state,
 * so the suite logs in exactly once per run. The credentials are the ones the
 * harness (.github/gui-tests/run.sh) guarantees on the container; a locally
 * started stack uses the same defaults.
 */
const { test: setup, expect } = require('@playwright/test');

const USER = process.env.JCB_ADMIN_USER || 'jcbgui';
const PASS = process.env.JCB_ADMIN_PASS || 'Jcb-Gui-Tests-2026!';

setup('log into the Joomla administrator', async ({ page }) => {
	await page.goto('/administrator/index.php');

	// The Atum login module. The field names are stable across Joomla 4-6
	// even where the surrounding markup is not.
	await page.locator('input[name="username"]').fill(USER);
	await page.locator('input[name="passwd"]').fill(PASS);
	await page.locator('#btn-login-submit, button[type="submit"]').first().click();

	// The administrator shell proves the login: the header module carries the
	// logout action, and the login form is gone.
	await expect(page.locator('input[name="passwd"]')).toHaveCount(0);
	await expect(page.locator('#wrapper, .com-cpanel, #content')).not.toHaveCount(0);

	await page.context().storageState({ path: '.auth/admin.json' });
});
