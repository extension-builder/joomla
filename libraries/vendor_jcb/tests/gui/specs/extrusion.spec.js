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
 * The harvest is aimed at a folder the installed extension itself ships
 * (the Extrusion Registry classes), so the source is always present in the
 * container and the harvest result is stable run over run.
 */
const { test, expect } = require('@playwright/test');
const { openView, setRadio } = require('../helpers/jcb');

const WEBROOT = process.env.JCB_WEBROOT || '/var/www/html';
const LIBRARY = WEBROOT
	+ '/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Extrusion/Registry';

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

		// the source fieldset
		for (const name of ['path', 'admin_path', 'site_path', 'libraries', 'dump']) {
			await expect(page.locator('[name="' + name + '"]')).toBeAttached();
		}
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

		// the noticeboard the compiler view carries stands here too -- twice,
		// like the compiler page: once on setup, once on the running pane
		await expect(page.locator('#extrusion-pane-setup #noticeboard')).toBeAttached();
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

		// the powers of that folder stand in the board, grouped and counted
		const powers = page.locator('details[data-extrusion-kind="power"]');
		await expect(powers).toBeVisible();
		const rows = powers.locator('.extrusion-row');
		expect(await rows.count()).toBeGreaterThan(5);

		// every row offers the three decisions, create-new first
		const first = rows.first();
		const actions = first.locator('[data-extrusion-act]');
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
		await page.getByRole('button', { name: 'Import into JCB' }).click();
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
});
