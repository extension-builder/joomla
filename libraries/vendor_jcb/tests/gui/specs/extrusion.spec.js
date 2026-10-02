// @ts-check
'use strict';

/**
 * The extrusion admin view, end to end.
 *
 * These specs drive the real page in a real Joomla administrator against the
 * container the harness stands up — the AJAX harvest, the pairing board and
 * the import all round-trip through the installed extension, not through
 * mocks. The import runs as a dry run, so the suite proves the whole pipeline
 * without writing a single row into the site it runs on.
 *
 * The harness seeds an isolated A/B/shared catalogue and independent source
 * fixtures before these read-only journeys. Its separate integration phase
 * checks actual persistence and compiler output, then restores fixture rows.
 */
const { test, expect } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');

const WEBROOT = process.env.JCB_WEBROOT || '/var/www/html';
const fixturePath = process.env.JCB_EXTRUSION_FIXTURES;
if (!fixturePath) {
	throw new Error('Run the disposable GUI harness to seed the extrusion fixtures.');
}
const fixtures = JSON.parse(require('node:fs').readFileSync(fixturePath, 'utf8'));
const LIBRARY = fixtures.library_new;
const COMPONENT_ADMIN = WEBROOT + '/administrator/components/com_componentbuilder';
const COMPONENT_SITE = WEBROOT + '/components/com_componentbuilder';

test.describe('Extrusion view access permissions', () => {
	/** Open an isolated native session, independent of the Super User state. */
	async function session(browser, role) {
		const context = await browser.newContext({
			baseURL: process.env.JCB_BASE_URL || 'http://localhost:8080',
			storageState: { cookies: [], origins: [] }
		});
		const page = await context.newPage();
		await page.goto('/administrator/index.php');
		await page.locator('input[name="username"]').fill('jcb_gui_extrusion_' + role);
		await page.locator('input[name="passwd"]').fill('Jcb-Gui-Acl-2026!');
		await page.locator('#btn-login-submit, button[type="submit"]').first().click();
		await expect(page.locator('input[name="passwd"]')).toHaveCount(0);
		return { context, page };
	}

	test('view access alone exposes import and completes a real dry run', async ({ browser }) => {
		const { context, page } = await session(browser, 'access');
		try {
			await openView(page, 'extrusion');
			await expect(page.getByRole('button', { name: 'Harvest the source' })).toBeVisible();
			await expect(page.locator('#extrusion-import-button')).toBeAttached();
			expect(await page.evaluate(() => /** @type {any} */ (window).JCBExtrusion.canImport)).toBe(true);
			await setRadio(page, 'show_advanced_options', '1');
			await setRadio(page, 'dry_run', '1');
			await page.locator('[name="libraries"]').fill(LIBRARY);
			await page.getByRole('button', { name: 'Harvest the source' }).click();
			await expect(page.locator('#extrusion-pane-pairing')).toBeVisible({ timeout: 120_000 });
			const button = page.getByRole('button', { name: 'Import into JCB', exact: true });
			await expect(button).toBeEnabled({ timeout: 120_000 });
			const response = page.waitForResponse((result) => result.url().includes('extrusionImport')
				&& result.request().method() === 'POST');
			await button.click();
			const confirmation = page.getByRole('dialog', { name: 'Confirm import', exact: true });
			if (await confirmation.isVisible()) {
				await confirmation.getByRole('button', { name: 'Acknowledge and import', exact: true }).click();
			}
			const imported = await response;
			expect(imported.status()).toBe(200);
			const result = await imported.json();
			expect(result.error, JSON.stringify(result)).toBeUndefined();
			expect(result.plan.status).toBe('preview');
			expect(result.plan.writes || []).toEqual([]);
			await expect(page.locator('#extrusion-pane-results .alert-success').first()).toBeVisible();
			await expect(page.getByText('nothing was written', { exact: false }).first()).toBeVisible();
		} finally {
			await context.close();
		}
	});

	test('denied view access hides import and blocks a direct authenticated AJAX attempt', async ({ browser }) => {
		const { context, page } = await session(browser, 'denied');
		try {
			await openView(page, 'extrusion');
			await expect(page).toHaveURL(/\/administrator\/index\.php\?option=com_componentbuilder$/);
			await expect(page.getByText('Not authorised!', { exact: true })).toBeVisible();
			await expect(page.locator('#extrusion-import-button')).toHaveCount(0);
			expect(await page.evaluate(() => typeof /** @type {any} */ (window).JCBExtrusion)).toBe('undefined');
			const token = await page.evaluate(() => {
				const joomla = /** @type {any} */ (window).Joomla;
				return joomla && joomla.getOptions('csrf.token') || Array.from(document.querySelectorAll('input[type="hidden"]'))
					.map((input) => /** @type {HTMLInputElement} */ (input))
					.find((input) => /^[a-f0-9]{32}$/.test(input.name) && input.value === '1')?.name;
			});
			expect(token, 'the denied session still submits its real native CSRF token').toMatch(/^[a-f0-9]{32}$/);
			const result = await page.request.post('/administrator/index.php?option=com_componentbuilder&format=json&raw=true&'
				+ token + '=1&task=ajax.extrusionImport', {
				multipart: { config: JSON.stringify({ dry_run: 1, libraries: [LIBRARY] }), decisions: '{}' }
			});
			expect(result.status()).toBe(200);
			expect(await result.json()).toEqual({ error: 'You do not have permission to import with the extrusion tool.' });
		} finally {
			await context.close();
		}
	});
});

test.describe('the JCB dashboard and menu', () => {
	test('offer the extrusion view next to the compiler', async ({ page }) => {
		await openView(page, 'componentbuilder');

		const icons = page.locator('.dashboard-icons .dashboard-icon-link');
		await expect(icons.first()).toBeVisible();

		const sources = await icons.locator('img').evaluateAll(
			(images) => images.map((img) => img.getAttribute('src') || '')
		);
		const compiler = sources.findIndex((src) => src.includes('compiler.png'));
		const extrusion = sources.findIndex((src) => src.includes('extrusion.png'));

		expect(compiler, 'the compiler tile stands on the dashboard').toBeGreaterThanOrEqual(0);
		expect(extrusion, 'the extrusion tile stands on the dashboard').toBeGreaterThanOrEqual(0);
		expect(extrusion, 'the extrusion tile stands right next to the compiler').toBe(compiler + 1);

		// and the administrator menu carries the view under the component
		const menuLink = page.locator(
			'a[href*="option=com_componentbuilder"][href*="view=extrusion"]'
		);
		await expect(menuLink.first()).toBeAttached();
	});
});

test.describe('the extrusion view', () => {
	test.beforeEach(async ({ page }) => {
		await openView(page, 'extrusion');
	});

	test('presents the whole setup surface', async ({ page }) => {
		// the three-step tab strip: only setup is open before a harvest
		await expect(page.locator('#extrusion-tab-setup')).toHaveClass(/active/);
		await expect(page.locator('#extrusion-tab-pairing')).toBeDisabled();
		await expect(page.locator('#extrusion-tab-results')).toBeDisabled();

		// the source fieldset: two component folders and the libraries, each
		// with its own select button -- and no combined path, no SQL dump
		for (const name of ['admin_path', 'site_path', 'libraries']) {
			await expect(page.locator('[name="' + name + '"]')).toBeAttached();
			await expect(page.locator('[data-extrusion-pick="' + name + '"]')).toBeVisible();
		}
		await expect(page.locator('[name="path"]')).toHaveCount(0);
		await expect(page.locator('[name="dump"]')).toHaveCount(0);
		await expect(page.locator('select[name="component_id"]')).toBeAttached();

		// the switches
		for (const name of ['mode', 'on_existing', 'scope_admin', 'scope_site',
			'scope_site_views', 'scope_tabs', 'scope_conditions', 'scope_language',
			'scope_translations', 'scope_relations', 'scope_component_details']) {
			await expect(
				page.locator('input[type="radio"][name="' + name + '"]').first()
			).toBeAttached();
		}

		// the advanced options stay hidden until asked for, then show
		await expect(page.locator('[name="language_tag"]')).toBeHidden();
		await setRadio(page, 'show_advanced_options', '1');
		await expect(page.locator('[name="language_tag"]')).toBeVisible();
		await expect(page.locator('[name="depth"]')).toBeVisible();
		await expect(page.locator('[name="max_files"]')).toBeVisible();
		await expect(
			page.locator('input[type="radio"][name="dry_run"]').first()
		).toBeAttached();

		// the social feed is gone -- its script never loaded on this page --
		// while the banner block stays
		await expect(page.locator('#noticeboard')).toHaveCount(0);
	});

	test('walks the site to select a folder, never typing it', async ({ page }) => {
		await page.locator('[data-extrusion-pick="admin_path"]').click();

		const modal = page.locator('#extrusion-folder-modal');
		await expect(modal).toBeVisible();
		await expect(page.locator('#extrusion-folder-path')).toHaveText('Site root');

		// walk root -> administrator -> components -> com_componentbuilder
		for (const folder of ['administrator', 'administrator/components',
			'administrator/components/com_componentbuilder']) {
			await modal.locator('[data-extrusion-folder="' + folder + '"]').click();
			await expect(page.locator('#extrusion-folder-path')).toHaveText(folder);
		}

		await page.locator('#extrusion-folder-choose').click();
		await expect(modal).toBeHidden();
		await expect(page.locator('[name="admin_path"]')).toHaveValue(COMPONENT_ADMIN);
	});

	test('refuses to harvest thin air, on the page', async ({ page }) => {
		await page.getByRole('button', { name: 'Harvest the source' }).click();

		const notice = page.locator('#extrusion-setup-notice');
		await expect(notice).toBeVisible();
		await expect(notice).toContainText('library folder');

		// and the page stayed on setup rather than pretending to run
		await expect(page.locator('#extrusion-pane-setup')).toBeVisible();
	});

	test('harvests a library, pairs it, and imports it as a dry run', async ({ page }) => {
		// a dry run is read from the form at harvest time, so it goes first
		await setRadio(page, 'show_advanced_options', '1');
		await setRadio(page, 'dry_run', '1');

		await page.locator('[name="libraries"]').fill(LIBRARY);
		await page.getByRole('button', { name: 'Harvest the source' }).click();

		// the harvest lands on the pairing board
		const pairing = page.locator('#extrusion-pane-pairing');
		await expect(pairing).toBeVisible({ timeout: 120_000 });
		await expect(page.locator('#extrusion-tab-pairing')).toBeEnabled();

		// the powers of that folder stand in the board, grouped and counted,
		// and the catalogue of existing definitions loaded without complaint
		await expect(page.locator('[data-extrusion-warning="catalogue"]')).toHaveCount(0);
		const powers = page.locator('details[data-extrusion-kind="power"]');
		await expect(powers).toBeVisible();
		const rows = powers.locator('.extrusion-row');
		expect(await rows.count()).toBeGreaterThan(5);

		// every row offers the three decisions, create-new first
		const first = rows.first();
		// the decisions are the buttons carrying the decision class; the
		// weight badge beside them acts through the same board handler
		const actions = first.locator('.extrusion-act');
		await expect(actions.nth(0)).toHaveText('Create new');
		await expect(actions.nth(2)).toHaveText('Ignore');

		// an explicit decision marks the row and can be taken back
		await first.locator('[data-extrusion-act="ignore"]').click();
		const marked = powers.locator('.extrusion-row.explicit').first();
		await expect(marked).toBeVisible();
		await expect(marked.locator('[data-extrusion-act="ignore"]')).toHaveClass(/active/);
		await marked.locator('[data-extrusion-act="reset"]').click();
		await expect(powers.locator('.extrusion-row.explicit')).toHaveCount(0);

		// bulk work over ticked rows
		await powers.locator('.extrusion-row input[type="checkbox"]').nth(0).check();
		await powers.locator('.extrusion-row input[type="checkbox"]').nth(1).check();
		await expect(page.locator('#extrusion-selected-count')).toHaveText('2');
		await page.locator('[data-extrusion-bulk="ignore"]').click();
		await expect(powers.locator('.extrusion-row.explicit')).toHaveCount(2);
		await powers.locator('.extrusion-row.explicit input[type="checkbox"]').nth(0).check();
		await powers.locator('.extrusion-row.explicit input[type="checkbox"]').nth(1).check();
		await page.locator('[data-extrusion-bulk="reset"]').click();
		await expect(powers.locator('.extrusion-row.explicit')).toHaveCount(0);

		// the filter narrows the tree
		const total = await rows.count();
		await page.locator('#extrusion-filter').fill('Report');
		const visible = await rows.evaluateAll(
			(all) => all.filter((row) => row.style.display !== 'none').length
		);
		expect(visible).toBeGreaterThan(0);
		expect(visible).toBeLessThan(total);
		await page.locator('#extrusion-filter').fill('');

		// the shared target picker opens, searches, and closes
		await first.locator('[data-extrusion-act="update"]').click();
		const modal = page.locator('#extrusion-modal');
		await expect(modal).toBeVisible();
		await modal.locator('#extrusion-modal-search').fill('zzz-nothing-matches-this');
		const options = modal.locator('.extrusion-modal-row');
		if (await options.count() === 0) {
			await expect(modal.locator('.extrusion-modal-empty')).toBeVisible();
		}
		await modal.locator('#extrusion-modal-close').click();
		await expect(modal).toBeHidden();

		// the import runs the whole pipeline and reports on the page
		await importReviewed(page);
		const results = page.locator('#extrusion-pane-results');
		await expect(results).toBeVisible({ timeout: 120_000 });
		await expect(page.locator('#extrusion-tab-results')).toBeEnabled();
		await expect(results.locator('.alert-success').first()).toBeVisible();

		// the dry run says plainly that nothing was written
		await expect(results.getByText('nothing was written', { exact: false }).first())
			.toBeVisible();

		// and the way back to setup stays open
		await page.locator('#extrusion-tab-setup').click();
		await expect(page.locator('#extrusion-pane-setup')).toBeVisible();
	});

	test('says what every row would change, and shows it line by line', async ({ page }) => {
		await page.locator('[name="libraries"]').fill(LIBRARY);
		await page.getByRole('button', { name: 'Harvest the source' }).click();

		const pairing = page.locator('#extrusion-pane-pairing');
		await expect(pairing).toBeVisible({ timeout: 120_000 });

		// the board arrives already saying what it would change: nothing has
		// been imported, and nobody has had to ask
		const powers = page.locator('details[data-extrusion-kind="power"]');
		const badges = powers.locator('.extrusion-change');
		expect(await badges.count(),
			'every power row carries what it would change').toBeGreaterThan(5);

		// these powers do not stand in this component yet, so every one of
		// them is an addition and none of them takes anything away
		const first = badges.first();
		await expect(first).toContainText('+');
		await expect(first.locator('.extrusion-add')).not.toHaveText('+0');
		await expect(first.locator('.extrusion-del')).toHaveText('\u22120');

		// nothing of the diff is on the page until a person asks for it
		await expect(page.locator('.extrusion-diff')).toHaveCount(0);

		await first.click();
		const diff = page.locator('.extrusion-diff').first();
		await expect(diff).toBeVisible({ timeout: 120_000 });
		await expect(diff.locator('.extrusion-diff-table').first()).toBeVisible();
		expect(await diff.locator('.extrusion-diff-line.add').count(),
			'a record that would be created is all additions').toBeGreaterThan(0);

		// it is a reading, not an editing: nothing in it can be typed into
		await expect(diff.locator('input, textarea, select, [contenteditable="true"]'))
			.toHaveCount(0);

		// closing it takes it off the page entirely
		await first.click();
		await expect(page.locator('.extrusion-diff')).toHaveCount(0);

		// a decision moves what the row would write, so its weight is read
		// again under the pairing it has now: ignoring a row takes it out of
		// the run, and out of the run there is nothing to weigh
		const row = powers.locator('.extrusion-row').first();
		await row.getByRole('button', { name: 'Ignore' }).click();
		await expect(row.locator('.extrusion-change'),
			'a row taken out of the run carries no weight').toHaveCount(0, { timeout: 120_000 });

		// and putting it back weighs it again, as an addition once more
		await row.locator('[data-extrusion-act="reset"]').click();
		await expect(row.locator('.extrusion-change .extrusion-del'),
			'a row put back into the run is weighed again').toHaveText('\u22120', { timeout: 120_000 });
		await expect(row.locator('.extrusion-change .extrusion-add')).not.toHaveText('+0');
	});


	/** A response is observed, never mocked: assertions inspect the real contract. */
	function responseFor(page, task, timeout = 120_000) {
		return page.waitForResponse((response) => response.url().includes(task)
			&& response.request().method() === 'POST', { timeout });
	}

	async function harvestFixture(page, library, target, dry = true) {
		await setRadio(page, 'show_advanced_options', '1');
		await setRadio(page, 'dry_run', dry ? '1' : '0');
		await page.locator('[name="component_id"]').selectOption(String(target));
		await page.locator('[name="libraries"]').fill(library);
		const response = responseFor(page, 'extrusionHarvest');
		await page.getByRole('button', { name: 'Harvest the source' }).click();
		const payload = await (await response).json();
		expect(payload.error, JSON.stringify(payload)).toBeUndefined();
		await expect(page.locator('#extrusion-pane-pairing')).toBeVisible();
		return payload;
	}

	/** Accept only the review shown by the real page, including dry-run scope reviews. */
	async function importReviewed(page) {
		await page.getByRole('button', { name: 'Import into JCB' }).click();
		const confirmation = page.getByRole('dialog', { name: 'Confirm import', exact: true });
		if (await confirmation.isVisible()) {
			await confirmation.getByRole('button', { name: 'Acknowledge and import', exact: true }).click();
		}
	}

	/** Read a string field from the actual browser's multipart form request. */
	function postedJson(request, name) {
		const match = (request.postData() || '').match(new RegExp('name="' + name + '"\\r?\\n\\r?\\n([^\\r\\n]+)'));
		expect(match, 'the browser posts the reviewed ' + name + ' field').not.toBeNull();
		return JSON.parse(match[1]);
	}

	function powerRow(page, key) {
		return page.locator('[data-extrusion-row="power|' + key + '"]');
	}

	for (const failure of ['http', 'json', 'network']) {
		test('distinguishes a real ' + failure + ' failure and recovers the harvest controls', async ({ page, context }) => {
			await page.locator('[name="libraries"]').fill(LIBRARY);
			let imports = 0;
			page.on('request', (request) => {
				if (request.url().includes('extrusionImport')) {
					imports++;
				}
			});
			// Exercise the actual browser/server transport, without fulfilling
			// or replacing an AJAX response. Apache answers the missing path;
			// Joomla answers its regular HTML page; offline fetch really rejects.
			const original = await page.evaluate((kind) => {
				const bootstrap = /** @type {any} */ (window).JCBExtrusion;
				const url = bootstrap.url;
				if (kind === 'http') {
					bootstrap.url = '/__jcb_missing_transport__/request?task=';
				} else if (kind === 'json') {
					bootstrap.url = location.pathname + '?jcb_transport=';
				}
				return url;
			}, failure);
			const response = failure === 'network' ? null : responseFor(page, 'extrusionHarvest');
			try {
				if (failure === 'network') {
					await context.setOffline(true);
				}
				await page.getByRole('button', { name: 'Harvest the source' }).click();
				const notice = page.locator('#extrusion-setup-notice');
				await expect(notice).toBeVisible();
				if (failure === 'network') {
					await expect(notice).toContainText('connection failed before a response');
					await expect(notice).not.toContainText('HTTP');
				} else {
					const actual = await response;
					expect(actual.status()).toBe(failure === 'http' ? 404 : 200);
					await expect(notice).toContainText('(HTTP ' + actual.status() + ')');
					await expect(notice).toContainText(failure === 'http'
						? 'unsuccessful response' : 'not valid JSON');
					await expect(notice).not.toContainText('could not reach the server');
				}
				await expect(notice.locator('*'), 'server HTML is never inserted into a notice').toHaveCount(0);
				await expect(page.locator('#extrusion-pane-running')).toBeHidden();
				await expect(page.getByRole('button', { name: 'Harvest the source' })).toBeEnabled();
				await expect(page.locator('#extrusion-tab-pairing')).toBeDisabled();
				expect(imports, 'failed read-only requests must never trigger an import').toBe(0);
			} finally {
				await context.setOffline(false);
				await page.evaluate((url) => { /** @type {any} */ (window).JCBExtrusion.url = url; }, original);
			}
			// Recovery is a new, explicit click and a real successful harvest.
			const recovered = await harvestFixture(page, fixtures.library_b, fixtures.component_b_id);
			expect(recovered.plan.status).toBe('preview');
			expect(imports).toBe(0);
		});
	}

	test('returns a safe phase and correlation reference for a real PHP error', async ({ page }) => {
		const response = await page.evaluate(async () => {
			const body = new FormData();
			// A nested path is malformed input to trim(), raising a PHP TypeError
			// inside the real model boundary before any source or database write.
			body.append('config', JSON.stringify({ libraries: [['private-source-marker']], dry_run: 1 }));
			const result = await fetch(/** @type {any} */ (window).JCBExtrusion.url + 'extrusionHarvest',
				{ method: 'POST', body });
			return { status: result.status, payload: await result.json() };
		});
		expect(response.status).toBe(200);
		expect(response.payload.error).toContain('server could not complete this operation');
		expect(response.payload.failure).toMatchObject({
			kind: 'operation', phase: 'configure', last_completed_phase: 'validated'
		});
		expect(response.payload.failure.reference).toMatch(/^[a-f0-9]{16}$/);
		expect(JSON.stringify(response.payload)).not.toMatch(/TypeError|trim\(|private-source-marker|AjaxModel\.php/);
	});

	test('never repeats an interrupted import and requires fresh approval after recovery', async ({ page, context }) => {
		const harvested = await harvestFixture(page, fixtures.library_b, fixtures.component_b_id);
		const consumer = harvested.powers.classes.find((candidate) => candidate.class === 'Consumer');
		let attempts = 0;
		page.on('request', (request) => {
			if (request.url().includes('extrusionImport')) {
				attempts++;
			}
		});
		try {
			await context.setOffline(true);
			await importReviewed(page);
			await expect(page.locator('#extrusion-pane-results')).toBeVisible();
			await expect(page.locator('#extrusion-results .alert-danger'))
				.toContainText('connection failed before a response');
			await expect(page.locator('#extrusion-pane-running')).toBeHidden();
			await page.locator('#extrusion-tab-pairing').click();
			await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeDisabled();
		} finally {
			await context.setOffline(false);
		}
		const preview = responseFor(page, 'extrusionWeigh');
		// Review a leaf change: ignoring Factory would correctly block its Consumer.
		await powerRow(page, consumer.source_key)
			.getByRole('button', { name: 'Ignore', exact: true }).click();
		const recovered = await (await preview).json();
		expect(recovered.plan.status, JSON.stringify(recovered.plan)).toBe('preview');
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeEnabled();
		expect(attempts, 'restoring the connection and previewing must not retry the import').toBe(1);
	});

	test('shows B’s actual GUID and recalculates complete pairings across A → B → A', async ({ page }) => {
		const payload = await harvestFixture(page, fixtures.library_b, fixtures.component_b_id);
		const factory = payload.powers.classes.find((candidate) => candidate.class === 'Factory');
		const consumer = payload.powers.classes.find((candidate) => candidate.class === 'Consumer');
		expect(factory.matched_guid).toBe(fixtures.factory_b);
		expect(consumer.dependencies.some((dependency) => dependency.guid === fixtures.factory_b)).toBe(true);
		await expect(powerRow(page, factory.source_key).locator('.extrusion-target-name'))
			.toHaveText('Extrusion Fixture Factory B');
		await expect(powerRow(page, factory.source_key).locator('.extrusion-target-guid')).toHaveText(fixtures.factory_b);
		await expect(powerRow(page, factory.source_key).locator('.extrusion-target-namespace'))
			.toHaveText('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory');
		const originalKeys = payload.powers.classes.map((candidate) => candidate.source_key).sort();
		for (const target of [fixtures.component_a_id, fixtures.component_b_id, fixtures.component_a_id, fixtures.component_b_id]) {
			const response = responseFor(page, 'extrusionWeigh');
			await page.locator('#extrusion-component-select').selectOption(String(target));
			await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeDisabled();
			const changed = await (await response).json();
			expect(changed.error, JSON.stringify(changed)).toBeUndefined();
			expect(changed.powers.classes.map((candidate) => candidate.source_key).sort()).toEqual(originalKeys);
			const row = changed.powers.classes.find((candidate) => candidate.class === 'Factory');
			const wanted = target === fixtures.component_b_id ? fixtures.factory_b : fixtures.factory_a;
			expect(row.matched_guid).toBe(wanted);
			await expect(powerRow(page, factory.source_key).locator('.extrusion-target-guid')).toHaveText(wanted);
			await expect(page.getByRole('dialog', { name: 'Confirm import', exact: true })).toBeHidden();
		}
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeEnabled();
	});

	test('leaves ambiguous identities unresolved until a real manual target is selected', async ({ page }) => {
		const payload = await harvestFixture(page, fixtures.library_ambiguous, 0);
		const factory = payload.powers.classes.find((candidate) => candidate.class === 'Factory');
		expect(factory.status).toBe('ambiguous');
		expect(factory.matched_guid).toBeNull();
		await expect(page.locator('#extrusion-ambiguity-notice')).toContainText(/ambiguous/i);
		await expect(page.locator('#extrusion-show-ambiguous')).toBeVisible();
		await page.locator('#extrusion-show-ambiguous').click();
		await expect(page.getByLabel('Matching status', { exact: true })).toHaveValue('ambiguous');
		await expect(page.locator('#extrusion-review-notice')).not.toContainText('[object Object]');
		await expect(powerRow(page, factory.source_key).locator('.extrusion-target-guid')).toHaveCount(0);
		await expect(powerRow(page, factory.source_key).locator('.extrusion-match-status')).toContainText('Ambiguous');
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeDisabled();
		await powerRow(page, factory.source_key).getByRole('button', { name: 'Update', exact: false }).click();
		const modal = page.locator('#extrusion-modal');
		await expect(modal).toBeVisible();
		const search = responseFor(page, 'extrusionCatalogue');
		await modal.locator('#extrusion-modal-search').fill(fixtures.ambiguous_factory_b);
		await search;
		const response = responseFor(page, 'extrusionWeigh');
		await modal.getByRole('button', { name: 'Extrusion Ambiguous Factory B', exact: false }).click();
		const corrected = await (await response).json();
		const actual = corrected.powers.classes.find((candidate) => candidate.source_key === factory.source_key);
		expect(actual.matched_guid).toBe(fixtures.ambiguous_factory_b);
		await expect(powerRow(page, factory.source_key).locator('.extrusion-target-guid')).toHaveText(fixtures.ambiguous_factory_b);
		// A different unresolved source cannot be concealed by fixing only Factory.
		expect(corrected.powers.classes.find((candidate) => candidate.class === 'Consumer').status).toBe('ambiguous');
		expect(corrected.plan.status).toBe('blocked');
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeDisabled();
	});

	test('keeps compact status badges inline and actions below each row at desktop and narrow widths', async ({ page }, testInfo) => {
		const payload = await harvestFixture(page, fixtures.library_b, fixtures.component_b_id);
		const factory = payload.powers.classes.find((candidate) => candidate.class === 'Factory');
		const row = powerRow(page, factory.source_key);
		for (const viewport of [{ name: 'desktop', width: 1366, height: 1000 }, { name: 'narrow', width: 390, height: 844 }]) {
			await page.setViewportSize({ width: viewport.width, height: viewport.height });
			await row.scrollIntoViewIfNeeded();
			const title = row.locator('.extrusion-row-heading b');
			const badge = row.locator('.extrusion-row-heading .extrusion-match-status');
			await expect(title).toHaveText('Factory');
			await expect(badge).toHaveText('Matched');
			const titleBox = await title.boundingBox();
			const badgeBox = await badge.boundingBox();
			const identityBox = await row.locator('.extrusion-identity').boundingBox();
			const actionsBox = await row.locator('.extrusion-actions').boundingBox();
			if (viewport.name === 'narrow') {
				const toolbarBox = await page.locator('#extrusion-bulk-bar').boundingBox();
				expect(toolbarBox.y + toolbarBox.height, 'the narrow toolbar does not cover the row title').toBeLessThanOrEqual(titleBox.y);
			}
			expect(badgeBox.x, viewport.name + ': status follows the title on the same line').toBeGreaterThanOrEqual(titleBox.x + titleBox.width - 1);
			expect(Math.abs(badgeBox.y - titleBox.y), viewport.name + ': status stays inline').toBeLessThanOrEqual(8);
			expect(badgeBox.height, viewport.name + ': status remains a compact badge').toBeLessThanOrEqual(titleBox.height + 8);
			expect(actionsBox.y, viewport.name + ': actions follow the entire row identity').toBeGreaterThanOrEqual(identityBox.y + identityBox.height - 1);
			expect(await row.evaluate((element) => element.scrollWidth <= element.clientWidth + 1), viewport.name + ': the row does not overflow horizontally').toBe(true);
			await testInfo.attach('pairing-' + viewport.name + '.png', { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
		}
	});

	test('intersects all filters and selects only matching group rows for bulk decisions', async ({ page }) => {
		const payload = await harvestFixture(page, LIBRARY + '\n' + fixtures.library_b, fixtures.component_b_id);
		const powers = page.locator('details[data-extrusion-kind="power"]');
		const selectAll = powers.locator('> summary [data-extrusion-group]');
		const classes = payload.powers.classes;
		const report = classes.find((candidate) => candidate.class === 'Report');
		const factory = classes.find((candidate) => candidate.class === 'Factory');
		const total = classes.length;
		const newCount = classes.filter((candidate) => candidate.status === 'new').length;
		expect(newCount).toBeGreaterThan(1);
		expect(newCount).toBeLessThan(total);
		await expect(page.locator('#extrusion-total-count')).toHaveText(String(total));
		await expect(page.locator('#extrusion-visible-count')).toHaveText(String(total));
		await powerRow(page, factory.source_key).locator('[data-extrusion-check]').check();
		await expect(page.locator('#extrusion-selected-count')).toHaveText('1');
		await expect(selectAll).toHaveJSProperty('indeterminate', true);

		await page.getByLabel('Entity type', { exact: true }).selectOption('power');
		await page.getByLabel('Matching status', { exact: true }).selectOption('new');
		await page.getByLabel('Planned change', { exact: true }).selectOption('create');
		await expect(page.locator('#extrusion-visible-count')).toHaveText(String(newCount));
		await expect(powerRow(page, factory.source_key)).toBeHidden();
		await expect(powerRow(page, factory.source_key).locator('[data-extrusion-check]')).not.toBeChecked();
		await expect(page.locator('#extrusion-selected-count')).toHaveText('0');
		await page.locator('#extrusion-filter').fill('Report');
		await expect(page.locator('#extrusion-visible-count')).toHaveText('1');
		await expect(powerRow(page, report.source_key)).toBeVisible();
		await selectAll.check();
		await expect(page.locator('#extrusion-selected-count')).toHaveText('1');
		await expect(powerRow(page, report.source_key).locator('[data-extrusion-check]')).toBeChecked();
		await expect(selectAll).toBeChecked();

		// Reset all four filter axes. The one retained choice becomes a
		// partial group selection; newly revealed rows are not silently ticked.
		await page.locator('#extrusion-filter').fill('');
		for (const label of ['Entity type', 'Matching status', 'Planned change']) {
			await page.getByLabel(label, { exact: true }).selectOption('');
		}
		await expect(page.locator('#extrusion-visible-count')).toHaveText(String(total));
		await expect(page.locator('#extrusion-selected-count')).toHaveText('1');
		await expect(selectAll).toHaveJSProperty('indeterminate', true);
		const library = powers.locator('details.extrusion-library').filter({ has: powerRow(page, report.source_key) });
		const libraryAll = library.locator('> summary [data-extrusion-group]');
		await expect(libraryAll).toHaveJSProperty('indeterminate', true);
		await library.locator('> summary').click();
		await expect(library).not.toHaveAttribute('open', '');
		await libraryAll.check();
		await expect(library).not.toHaveAttribute('open', '');
		await expect(page.locator('#extrusion-selected-count')).toHaveText(String(newCount));
		await expect(libraryAll).toBeChecked();
		await expect(selectAll).toHaveJSProperty('indeterminate', true);
		await library.locator('> summary').click();
		await powerRow(page, report.source_key).locator('[data-extrusion-check]').uncheck();
		await expect(libraryAll).toHaveJSProperty('indeterminate', true);
		await expect(page.locator('#extrusion-selected-count')).toHaveText(String(newCount - 1));

		// Filtering away an entire selected library removes those selections.
		// The real request proves bulk Ignore cannot act on the hidden rows.
		await page.locator('#extrusion-filter').fill('Factory');
		await expect(page.locator('#extrusion-visible-count')).toHaveText('1');
		await expect(page.locator('#extrusion-selected-count')).toHaveText('0');
		await selectAll.check();
		const preview = responseFor(page, 'extrusionWeigh');
		await page.locator('[data-extrusion-bulk="ignore"]').click();
		const weighed = await preview;
		expect(postedJson(weighed.request(), 'decisions')).toEqual({ power: { [factory.source_key]: { action: 'ignore' } } });
		await expect(powerRow(page, factory.source_key).locator('[data-extrusion-act="ignore"]')).toHaveClass(/active/);
		await page.locator('#extrusion-filter').fill('');
		await expect(powers.locator('.extrusion-row.explicit')).toHaveCount(1);
		await expect(powerRow(page, report.source_key)).not.toHaveClass(/explicit/);
		await page.getByLabel('Planned change', { exact: true }).selectOption('ignore');
		await expect(page.locator('#extrusion-visible-count')).toHaveText('1');
		await page.getByLabel('Matching status', { exact: true }).selectOption('new');
		await expect(page.locator('#extrusion-visible-count')).toHaveText('0');
		await expect(selectAll).toBeDisabled();
		await expect(page.locator('#extrusion-selected-count')).toHaveText('0');
		for (const label of ['Matching status', 'Planned change']) {
			await page.getByLabel(label, { exact: true }).selectOption('');
		}
		await expect(page.locator('#extrusion-visible-count')).toHaveText(String(total));
		await expect(selectAll).not.toHaveJSProperty('indeterminate', true);
		await expect(powers.locator('.extrusion-row.explicit')).toHaveCount(1);
	});

	test('separates matched updates from matched definitions with no effective changes', async ({ page }) => {
		for (const sample of [{ library: fixtures.library_b, change: 'update' }, { library: fixtures.library_noop, change: 'nochange' }]) {
			await page.locator('#extrusion-tab-setup').click();
			const payload = await harvestFixture(page, sample.library, fixtures.component_b_id);
			const classes = payload.powers.classes;
			expect(classes).toHaveLength(2);
			for (const candidate of classes) {
				expect(candidate.status).toBe('matched');
				const weight = payload.changes['power|' + candidate.source_key];
				expect(weight, candidate.class + ' has an authoritative effective proposal').toBeDefined();
				expect(weight.changed, candidate.class + ' reflects its actual stored values').toBe(sample.change === 'update');
				if (sample.change === 'update') {
					expect(weight.action).toBe('update');
				}
			}
			await page.getByLabel('Entity type', { exact: true }).selectOption('power');
			await page.getByLabel('Matching status', { exact: true }).selectOption('matched');
			await page.getByLabel('Planned change', { exact: true }).selectOption(sample.change);
			await expect(page.locator('#extrusion-visible-count')).toHaveText('2');
			await expect(page.locator('#extrusion-board .extrusion-row:visible')).toHaveCount(2);
			await page.getByLabel('Planned change', { exact: true }).selectOption(sample.change === 'update' ? 'nochange' : 'update');
			await expect(page.locator('#extrusion-visible-count')).toHaveText('0');
			await expect(page.locator('#extrusion-board .extrusion-row:visible')).toHaveCount(0);
			for (const label of ['Entity type', 'Matching status', 'Planned change']) {
				await page.getByLabel(label, { exact: true }).selectOption('');
			}
			await expect(page.locator('#extrusion-visible-count')).toHaveText('2');
		}
	});

	test('loads only linked Powers until an exact manual target is requested', async ({ page }) => {
		const catalogueResponse = responseFor(page, 'extrusionCatalogue');
		const payload = await harvestFixture(page, fixtures.library_b, fixtures.component_b_id);
		const catalogue = await (await catalogueResponse).json();
		expect(catalogue.powers.map((power) => power.guid)).toContain(fixtures.factory_b);
		expect(catalogue.powers.map((power) => power.guid)).not.toContain(fixtures.factory_a);
		const factory = payload.powers.classes.find((candidate) => candidate.class === 'Factory');
		// A matched row names its current target on the update button.
		await powerRow(page, factory.source_key)
			.getByRole('button', { name: 'Extrusion Fixture Factory B', exact: true }).click();
		await expect(page.locator('#extrusion-power-search-hint')).toContainText('exact name');
		const lookup = responseFor(page, 'extrusionCatalogue');
		await page.locator('#extrusion-modal-search').fill(fixtures.factory_a);
		const answer = await lookup;
		expect(answer.request().postData()).toContain('power_search');
		const choices = await answer.json();
		expect(choices.powers.map((power) => power.guid)).toContain(fixtures.factory_a);
		await expect(page.locator('#extrusion-modal [data-extrusion-target="' + fixtures.factory_a + '"]')).toBeVisible();
		await page.locator('#extrusion-modal-close').click();
		await expect(powerRow(page, factory.source_key).locator('.extrusion-target-guid')).toHaveText(fixtures.factory_b);
		// Reading choices never approves a target or writes its definition.
	});

	test('confirms scoped imports only after the current plan is reviewed, and cancellation sends nothing', async ({ page }) => {
		const payload = await harvestFixture(page, fixtures.library_shared, fixtures.component_b_id);
		const shared = payload.powers.classes[0];
		expect(shared.matched_guid).toBe(fixtures.shared);
		expect(shared.write_scope).toBe('unestablished');
		expect(shared.namespace_proposal.value).toContain('Abstraction.Registry.Value');
		expect(payload.plan.required_approvals).toEqual(['unknown']);
		await expect(powerRow(page, shared.source_key).locator('.extrusion-target-guid')).toHaveText(fixtures.shared);
		await expect(page.locator('#extrusion-acknowledge-scopes')).toHaveCount(0);
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeEnabled();
		const requests = [];
		page.on('request', (request) => {
			if (request.url().includes('extrusionImport')) {
				requests.push(request);
			}
		});
		const confirmation = page.getByRole('dialog', { name: 'Confirm import', exact: true });
		await page.getByRole('button', { name: 'Import into JCB' }).click();
		await expect(confirmation).toBeVisible();
		await expect(confirmation).toContainText('I acknowledge that these changes can affect the system.');
		await expect(confirmation).not.toContainText('Usage not fully established');
		expect(requests, 'opening the review popup does not import').toHaveLength(0);
		await confirmation.getByRole('button', { name: 'Cancel', exact: true }).click();
		await expect(confirmation).toBeHidden();
		expect(requests, 'cancelling leaves all fixture definitions untouched').toHaveLength(0);

		const preview = responseFor(page, 'extrusionWeigh');
		await page.locator('#extrusion-component-select').selectOption(String(fixtures.component_a_id));
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeDisabled();
		const changed = await (await preview).json();
		expect(changed.plan.status, JSON.stringify(changed.plan)).toBe('preview');
		expect(changed.plan.fingerprint).not.toBe(payload.plan.fingerprint);
		await expect(confirmation).toBeHidden();
		expect(requests).toHaveLength(0);
		await expect(page.getByRole('button', { name: 'Import into JCB' })).toBeEnabled();
		await page.getByRole('button', { name: 'Import into JCB' }).click();
		await expect(confirmation).toBeVisible();
		await expect(confirmation).toContainText('I acknowledge that these changes can affect the system.');
		await expect(confirmation).not.toContainText('Usage not fully established');
		const response = responseFor(page, 'extrusionImport');
		await confirmation.getByRole('button', { name: 'Acknowledge and import', exact: true }).click();
		const imported = await response;
		const config = postedJson(imported.request(), 'config');
		expect(config.approved_plan).toBe(changed.plan.fingerprint);
		expect(config.acknowledged_scopes).toEqual(Object.fromEntries(changed.plan.required_approvals.map((scope) => [scope, true])));
		expect(String(config.dry_run)).toBe('1');
		const result = await imported.json();
		expect(result.error, JSON.stringify(result)).toBeUndefined();
		expect(result.plan.fingerprint).toBe(changed.plan.fingerprint);
		expect(result.plan.writes || []).toEqual([]);
		expect(requests).toHaveLength(1);
		await expect(page.locator('#extrusion-pane-results .alert-success').first()).toBeVisible();
	});

	test('imports the exact reviewed B plan in dry-run mode and retains aliased dependencies', async ({ page }) => {
		const payload = await harvestFixture(page, fixtures.library_b, fixtures.component_b_id);
		expect(payload.plan.status).toBe('preview');
		const response = responseFor(page, 'extrusionImport');
		await importReviewed(page);
		const result = await (await response).json();
		expect(result.error, JSON.stringify(result)).toBeUndefined();
		expect(result.plan.fingerprint).toBe(payload.plan.fingerprint);
		expect(result.plan.status).toBe('preview');
		expect(result.plan.writes || []).toEqual([]);
		const consumer = result.powers.classes.find((candidate) => candidate.class === 'Consumer');
		expect(consumer.dependencies.some((dependency) => dependency.guid === fixtures.factory_b)).toBe(true);
		await expect(page.locator('#extrusion-pane-results .alert-success').first()).toBeVisible();
	});

	test('harvests the installed component against the real schema', async ({ page }) => {
		await setRadio(page, 'show_advanced_options', '1');
		await setRadio(page, 'dry_run', '1');
		// the heaviest journey on the board: a real component, both folders,
		// resolved and paired against the live database -- the run that a
		// fabricated fixture schema can never stand in for
		test.setTimeout(420_000);

		await page.locator('[name="admin_path"]').fill(COMPONENT_ADMIN);
		await page.locator('[name="site_path"]').fill(COMPONENT_SITE);
		// the language sweep stays ON: an installed component keeps its
		// catalogue in the site's central language folders, and resolving
		// those constants is what lets harvested fields match by their real
		// names -- the translations scope alone is not under test here
		await setRadio(page, 'scope_translations', '0');

		await page.getByRole('button', { name: 'Harvest the source' }).click();

		// the harvest lands on the pairing board with no error on the page
		const pairing = page.locator('#extrusion-pane-pairing');
		await expect(pairing).toBeVisible({ timeout: 300_000 });

		// the catalogue of existing definitions answered from the real
		// database -- a schema mismatch anywhere in its queries shows here
		await expect(page.locator('[data-extrusion-warning="catalogue"]')).toHaveCount(0);

		// the component's admin views stand in the board with their fields
		const views = page.locator('details[data-extrusion-kind="admin_view"]');
		await expect(views).toBeVisible();
		expect(await views.locator('.extrusion-row').count()).toBeGreaterThan(10);
		await expect(views.locator('details.extrusion-fields').first()).toBeAttached();

		// columns many views state identically -- the guid field above all --
		// group into ONE field: the members render as shared rows that offer
		// only Detach, never the three decisions of an ordinary row
		const sharedRows = views.locator('.extrusion-row.extrusion-shared');
		expect(await sharedRows.count(),
			'A real component states the same field in many views, so the '
			+ 'board must show shared members').toBeGreaterThan(0);
		// A nested group selects its eligible fields even while collapsed,
		// without selecting attached shared members or unrelated parent rows.
		const groups = views.locator('details.extrusion-fields');
		const groupIndex = await groups.evaluateAll((elements) => elements.findIndex((element) => element.querySelectorAll('[data-extrusion-check]').length > 1));
		expect(groupIndex, 'a real view has multiple selectable field rows').toBeGreaterThanOrEqual(0);
		const nested = groups.nth(groupIndex);
		const fieldCount = await nested.locator('[data-extrusion-check]').count();
		const nestedAll = nested.locator('> summary [data-extrusion-group]');
		const viewsAll = views.locator('> summary [data-extrusion-group]');
		await nestedAll.check();
		await expect(page.locator('#extrusion-selected-count')).toHaveText(String(fieldCount));
		await expect(nestedAll).toBeChecked();
		await expect(viewsAll).toHaveJSProperty('indeterminate', true);
		await expect(sharedRows.locator('[data-extrusion-check]')).toHaveCount(0);
		await page.getByLabel('Entity type', { exact: true }).selectOption('admin_view');
		await expect(page.locator('#extrusion-selected-count')).toHaveText('0');
		const viewCount = await views.locator('> .extrusion-rows > .extrusion-row').count();
		await expect(page.locator('#extrusion-visible-count')).toHaveText(String(viewCount));
		await expect(nestedAll).toBeDisabled();
		await page.getByLabel('Entity type', { exact: true }).selectOption('');
		await expect(viewsAll).not.toHaveJSProperty('indeterminate', true);

		// the field groups render collapsed, so open them before interacting
		await page.evaluate(() => {
			document.querySelectorAll('details.extrusion-fields')
				.forEach((group) => { group.open = true; });
		});
		const member = sharedRows.first();
		await expect(member.locator('[data-extrusion-act="detach"]')).toBeVisible();
		await expect(member.locator('[data-extrusion-act="create"]')).toHaveCount(0);

		// detaching turns the member into an ordinary row, and reset
		// returns it to its group -- each render collapses the groups
		// again, so they are re-opened before every interaction
		const openGroups = () => page.evaluate(() => {
			document.querySelectorAll('details.extrusion-fields')
				.forEach((group) => { group.open = true; });
		});
		const memberId = await member.getAttribute('data-extrusion-row');
		await member.locator('[data-extrusion-act="detach"]').click();
		await openGroups();
		const detached = page.locator('[data-extrusion-row="' + memberId + '"]');
		await expect(detached.locator('[data-extrusion-act="create"]')).toBeVisible();
		await detached.locator('[data-extrusion-act="reset"]').click();
		await openGroups();
		await expect(page.locator('[data-extrusion-row="' + memberId + '"]')
			.locator('[data-extrusion-act="detach"]')).toBeVisible();

		// the administrator screens outside the tables -- compiler, import and
		// their kin -- stand on the board as custom admin views, because a
		// component is more than its tables and losing these screens was
		// exactly the failure this section exists to prevent
		const custom = page.locator('details[data-extrusion-kind="custom_admin_view"]');
		await expect(custom).toBeVisible();
		expect(await custom.locator('.extrusion-row').count()).toBeGreaterThan(0);

		// a custom admin view with a table view's code name is a contradiction:
		// the custom section must never offer any admin view of this same run
		const viewNames = new Set(
			(await views.locator('> .extrusion-rows > .extrusion-row .extrusion-identity b')
				.allTextContents())
				.map((text) => text.trim().toLowerCase())
		);
		const customNames = (await custom
			.locator('.extrusion-row .extrusion-identity b').allTextContents())
			.map((text) => text.trim().toLowerCase());
		const overlap = customNames.filter(
			(name) => viewNames.has(name) || viewNames.has(name.replace(/s$/, ''))
		);
		expect(
			overlap,
			'These table views were wrongly offered as custom admin views: '
			+ overlap.join(' | ')
		).toEqual([]);

		// and the fields of this very component resemble fields JCB already
		// holds -- shown as similar, offered for reuse, never forced onto them
		const similar = await views.locator('details.extrusion-fields summary')
			.allTextContents();
		expect(
			similar.some((text) => !text.includes(' 0 similar')),
			'At least one view\'s fields must resemble the fields JCB already holds: '
			+ similar.slice(0, 5).join(' | ')
		).toBe(true);

		// The installed component is weighed against the live schema without
		// durable mutations. The harness separately verifies actual Power writes
		// and compiler output, restoring its isolated fixtures afterwards.
		await importReviewed(page);
		const results = page.locator('#extrusion-pane-results');
		await expect(results).toBeVisible({ timeout: 300_000 });

		// a failed run must fail the suite SAYING what the page said,
		// so the report carries the server's own words into the CI log
		const raised = await results.locator('.alert-danger').allTextContents();
		expect(raised, 'The live import raised on the page: ' + raised.join(' | '))
			.toEqual([]);
		await expect(results.locator('.alert-success').first()).toBeVisible();
		await expect(results.getByText('nothing was written', { exact: false }).first()).toBeVisible();

		// the way back to setup stays open
		await page.locator('#extrusion-tab-setup').click();
		await expect(page.locator('#extrusion-pane-setup')).toBeVisible();
	});
});
