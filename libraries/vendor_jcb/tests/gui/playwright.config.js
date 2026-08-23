// @ts-check
'use strict';

/**
 * The Playwright configuration for the JCB GUI test suite.
 *
 * The suite runs against a joomengine container that carries this working
 * tree, brought up by .github/gui-tests/run.sh — the same way the golden
 * master brings up its container. The base URL and the administrator
 * credentials arrive through the environment, with the harness defaults
 * mirrored here so a locally started stack works without any exports.
 *
 * The suite runs serially on purpose: every spec drives one shared Joomla
 * administrator session against one shared site, and the AJAX-heavy views
 * (harvest, compile) hold server state a parallel run would race.
 */
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
	testDir: './specs',
	fullyParallel: false,
	workers: 1,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 1 : 0,
	timeout: 90_000,
	expect: { timeout: 15_000 },
	reporter: [
		['list'],
		['html', { open: 'never', outputFolder: 'playwright-report' }],
		['json', { outputFile: 'results.json' }]
	],
	use: {
		baseURL: process.env.JCB_BASE_URL || 'http://localhost:8080',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure'
	},
	projects: [
		{
			name: 'setup',
			testMatch: /global\.setup\.js/
		},
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
				storageState: '.auth/admin.json'
			},
			dependencies: ['setup']
		}
	]
});
