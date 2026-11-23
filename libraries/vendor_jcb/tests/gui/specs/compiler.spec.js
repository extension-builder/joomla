// @ts-check
'use strict';

/**
 * These journeys cover the repository-installed Compiler view. The harness
 * installs this working tree; it does not rebuild JCB from its definitions.
 * Component details use the existing disposable extrusion fixture and the
 * real AJAX endpoint without submitting a build or changing component data.
 */
const { readFileSync } = require('node:fs');
const { test, expect } = require('@playwright/test');
const { openView } = require('../helpers/jcb');

test('Compiler identifies its page and keeps all supported target versions selectable', async ({ page }) => {
	const navigation = page.waitForResponse(response =>
		response.request().isNavigationRequest()
		&& new URL(response.url()).searchParams.get('view') === 'compiler'
	);
	await openView(page, 'compiler');
	const response = await navigation;
	// The toolbar resolves the current user's help URL even when no help is configured.
	expect(response.status(), 'the compiler toolbar resolves help without a server error').toBe(200);
	await expect(page.locator('body'), 'help lookup does not print legacy User getter diagnostics')
		.not.toContainText(/User::get\(\)|Access directly the property from the user object|only available in compatibility mode/);
	await expect(page.getByRole('heading', { name: 'Compiler', exact: true }),
		'the imported compiler page supplies its toolbar title').toBeVisible();
	await expect(page.getByRole('heading', { name: 'Ready to compile a component', exact: true })).toBeVisible();
	await expect(page.locator('#joomla_version option')).toHaveText(['Joomla 3', 'Joomla 4', 'Joomla 5', 'Joomla 6']);
	for (const version of ['3', '4', '5', '6']) {
		await page.locator('#joomla_version').selectOption(version);
		await expect(page.locator('#joomla_version')).toHaveValue(version);
	}
});

test('Compiler loads component details and updates the preferred target without compiling', async ({ page }) => {
	const fixturePath = process.env.JCB_EXTRUSION_FIXTURES;
	if (!fixturePath) {
		throw new Error('Run the disposable GUI harness to seed the Compiler component fixture.');
	}
	const fixtures = JSON.parse(readFileSync(fixturePath, 'utf8'));
	const componentId = String(fixtures.component_a_id);
	expect(componentId, 'the harness supplies an isolated component to read').toMatch(/^[1-9]\d*$/);

	await openView(page, 'compiler');
	const component = page.locator('#adminForm #component_id');
	const version = page.getByLabel('Joomla Version', { exact: true });
	const details = page.locator('#component-details');
	// The separate compilation-progress panel has its own noticeboard.
	const noticeboard = page.locator('#form #noticeboard');
	const selectNotice = details.getByText('Please select a component that you would like to compile.', { exact: true });
	await expect(selectNotice).toBeVisible();
	// The fixture inherits the schema's preferred target of Joomla 3. Starting
	// on Joomla 6 makes the response-driven selection change observable.
	await version.selectOption('6');

	const tokenName = await page.evaluate(() => /** @type {any} */ (window).token);
	expect(tokenName, 'the inline request uses the native session token name').toMatch(/^[a-f0-9]{32}$/);
	await expect(page.locator(`#adminForm input[type="hidden"][name="${tokenName}"]`),
		'the request token matches the form token').toHaveValue('1');
	await page.evaluate(() => {
		const scope = /** @type {any} */ (window);
		scope.compilerVersionEvents = [];
		// Observe the real handler's notifications without firing or replacing them.
		scope.jQuery('#joomla_version').on('liszt:updated.compilerGui change.compilerGui', (event) => {
			scope.compilerVersionEvents.push({ type: event.type, value: event.currentTarget.value });
		});
	});

	const pending = page.waitForResponse(response =>
		new URL(response.url()).searchParams.get('task') === 'ajax.getComponentDetails',
	{ timeout: 60_000 });
	await component.selectOption({ label: 'Extrusion Fixture A' });
	await expect(component).toHaveValue(componentId);
	const response = await pending;
	const request = response.request();
	const requestUrl = new URL(request.url());
	const endpoint = new URL('index.php', page.url());
	expect(requestUrl.origin, 'component details stay on the installed administrator origin').toBe(endpoint.origin);
	expect(requestUrl.pathname, 'component details use the administrator AJAX front controller').toBe(endpoint.pathname);
	expect(request.method(), 'component details remain a read-only GET request').toBe('GET');
	expect(request.postData(), 'the selected component and token travel in the query string').toBeNull();
	expect(request.headers().accept, 'the request asks for the existing JSON response').toBe('application/json');
	expect([...requestUrl.searchParams], 'the AJAX query has no duplicate or extra parameters').toHaveLength(6);
	expect(Object.fromEntries(requestUrl.searchParams), 'the AJAX task, format, component and token are preserved').toEqual({
		option: 'com_componentbuilder',
		task: 'ajax.getComponentDetails',
		format: 'json',
		raw: 'true',
		[tokenName]: '1',
		id: componentId
	});
	expect(response.status(), 'the installed component answers the real AJAX request').toBe(200);
	const result = await response.json();
	expect(result.error, 'the details endpoint returns component information without an application error').toBeUndefined();
	expect(result.html).toContain('Extrusion Fixture A');
	expect(String(result.preferred_joomla_version), 'the seeded component retains its preferred target').toBe('3');
	await expect(details.getByRole('heading', { name: 'Extrusion Fixture A (v1.0.0)', exact: true }),
		'the returned details replace the selection notice').toBeVisible();
	// Joomla's decorative edit icon prefixes the accessible action name.
	await expect(details.getByRole('link', { name: /Edit Extrusion Fixture A$/ }))
		.toHaveAttribute('href', `index.php?option=com_componentbuilder&ref=compiler&view=joomla_components&task=joomla_component.edit&id=${componentId}`);
	await expect(version, 'the preferred Joomla target is selected').toHaveValue('3');
	await expect.poll(() => page.evaluate(() => /** @type {any} */ (window).compilerVersionEvents),
		'the legacy Chosen notification precedes the normal change notification').toEqual([
		{ type: 'liszt:updated', value: '3' },
		{ type: 'change', value: '3' }
	]);
	await expect(noticeboard).toBeHidden();
	await expect(page.locator('#compiler')).toBeHidden();
	await expect(page.locator('#compilerForm')).toBeVisible();

	await component.selectOption('');
	await expect(selectNotice, 'clearing the selection restores the original instruction').toBeVisible();
	await expect(details.getByRole('heading', { name: 'Extrusion Fixture A (v1.0.0)', exact: true })).toHaveCount(0);
	await expect(noticeboard, 'clearing the selection restores the noticeboard').toBeVisible();
	await expect(version, 'clearing the component does not overwrite the selected target').toHaveValue('3');
});

test('Compiler delivers its bundled Markdown parser', async ({ page }) => {
	await openView(page, 'compiler');
	await expect.poll(() => page.evaluate(() => typeof /** @type {any} */ (window).marked?.parse),
		'the installed Compiler page loads the bundled Marked library').toBe('function');
	// This is a test-only library invocation. The component-details flow does
	// not consume Marked, so its successful AJAX response cannot prove this.
	expect(await page.evaluate(() => /** @type {any} */ (window).marked.parse('**Compiler parser**')))
		.toBe('<p><strong>Compiler parser</strong></p>\n');
});

test('Compiler explains a missing component without submitting a build', async ({ page }) => {
	await openView(page, 'compiler');
	await page.locator('#component_id').selectOption('');
	await page.locator('#compilerForm').getByRole('button', { name: /Compile Component$/ }).click();
	await expect(page.getByText('You must select a component!', { exact: true }),
		'an empty selection gives the operator an actionable validation message').toBeVisible();
	await expect(page.locator('#compilerForm')).toBeVisible();
	await expect(page.locator('#compiler')).toBeHidden();
	await expect(page.locator('#component_id')).toHaveValue('');
});
