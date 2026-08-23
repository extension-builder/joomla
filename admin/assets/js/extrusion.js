/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * The extrusion page: harvest a source, pair every candidate with what the
 * target component already has, and import only what was approved.
 *
 * Language note: every user-facing string comes through the text map the
 * template prints (window.JCBExtrusion.text), where each one is a natural
 * string inside Text::_() -- never a language constant. JCB manages those
 * strings itself when this code is imported, so none may be added to the
 * language files by hand.
 */
(function () {
	'use strict';

	// This file loads in the document head, before the template's inline
	// bootstrap defines window.JCBExtrusion -- so the bootstrap is read
	// lazily, once the page stands, never at evaluation time.
	let E = { url: '', canImport: false, text: {} };
	let T = E.text;

	/**
	 * The page state: the harvest payload, the current catalogue of the
	 * target component, the explicit decisions of the person, and the
	 * rows ticked for bulk work.
	 */
	const state = {
		data: null,
		catalogue: null,
		decisions: {},
		selected: new Set(),
		modal: null
	};

	const $ = (id) => document.getElementById(id);
	const $$ = (selector, root) => Array.from((root || document).querySelectorAll(selector));

	const esc = (value) => String(value ?? '').replace(/[&<>"']/g,
		(c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));

	/**
	 * One POST to the ajax gateway.
	 */
	async function post(task, params) {
		const body = new FormData();
		Object.entries(params || {}).forEach(([key, value]) => body.append(key, value));
		const response = await fetch(E.url + task, { method: 'POST', body: body });
		if (!response.ok) {
			throw new Error(T.requestFailed);
		}
		return response.json();
	}

	/**
	 * Show one pane and mirror it in the tab strip.
	 */
	function showPane(name) {
		$$('.extrusion-pane').forEach((pane) => {
			pane.style.display = pane.dataset.extrusionPane === name ? 'block' : 'none';
		});
		$$('#extrusion-tabs .nav-link').forEach((tab) => {
			tab.classList.toggle('active', tab.dataset.extrusionTab === name);
		});
	}

	function enableTab(name) {
		const tab = document.querySelector('#extrusion-tabs [data-extrusion-tab="' + name + '"]');
		if (tab) {
			tab.disabled = false;
		}
	}

	/**
	 * Read the whole setup form into the run configuration.
	 */
	function readConfig() {
		const form = $('adminForm');
		const value = (name, fallback) => {
			const field = form.elements[name];
			return field ? field.value : fallback;
		};
		const radio = (name, fallback) => {
			const field = form.elements[name];
			if (!field) {
				return fallback;
			}
			return field.value !== undefined && field.value !== ''
				? field.value : fallback;
		};
		const componentField = value('component_id', '');
		const config = {
			path: value('path', '').trim(),
			admin_path: value('admin_path', '').trim(),
			site_path: value('site_path', '').trim(),
			dump: value('dump', '').trim(),
			libraries: value('libraries', '').split(/\r?\n/)
				.map((line) => line.trim()).filter((line) => line !== ''),
			component: componentField === '' ? 0 : parseInt(componentField, 10) || 0,
			detect: componentField === '',
			mode: radio('mode', 'create'),
			on_existing: radio('on_existing', 'update'),
			layout: value('layout', 'auto'),
			language_tag: value('language_tag', 'en-GB'),
			table_class: radio('table_class', 'auto'),
			dry_run: radio('dry_run', '0'),
			strict: radio('strict', '0'),
			depth: parseInt(value('depth', '12'), 10) || 12,
			max_files: parseInt(value('max_files', '20000'), 10) || 20000
		};
		['admin', 'site', 'site_views', 'tabs', 'conditions', 'language',
			'translations', 'relations', 'component_details'].forEach((scope) => {
			config['scope_' + scope] = radio('scope_' + scope, '1');
		});
		// radios render as a set, so read the checked one directly
		$$('#adminForm input[type="radio"]:checked').forEach((input) => {
			if (input.name in config || input.name.indexOf('scope_') === 0) {
				config[input.name] = input.value;
			}
		});
		return config;
	}

	/**
	 * Run the harvest and land on the pairing board.
	 */
	async function harvest() {
		const notice = $('extrusion-setup-notice');
		notice.style.display = 'none';
		const config = readConfig();
		if (config.path === '' && config.admin_path === '' && config.site_path === ''
			&& config.dump === '' && config.libraries.length === 0) {
			notice.textContent = T.needSource;
			notice.style.display = 'block';
			return;
		}
		state.config = config;
		$('extrusion-running-title').textContent = config.path || T.theSource;
		$('extrusion-running-verb').textContent = T.harvesting;
		showPane('running');
		let payload;
		try {
			payload = await post('extrusionHarvest', { config: JSON.stringify(config) });
		} catch (error) {
			payload = { error: error.message || T.requestFailed };
		}
		// only an answer that says success is one: a missing or malformed
		// payload must land back on setup with a message, never on an empty
		// pairing board
		if (!payload || payload.error || !payload.success) {
			showPane('setup');
			notice.textContent = (payload && payload.error) ? payload.error : T.harvestFailed;
			notice.style.display = 'block';
			return;
		}
		// the powers arrive raw from the harvest, so they take their board
		// identity here, once -- before anything renders or looks them up
		if (payload.powers && payload.powers.classes) {
			payload.powers.classes.forEach((candidate) => {
				candidate.kind = 'power';
				candidate.key = candidate.guid;
				candidate.label = candidate.class;
				candidate.detail = candidate.fqn;
			});
		}
		state.data = payload;
		state.decisions = {};
		state.selected.clear();
		enableTab('setup');
		enableTab('pairing');
		fillComponentSelect(payload);
		await loadCatalogue(payload.component || 0);
		renderBoard();
		showPane('pairing');
	}

	/**
	 * Offer every published component as a pairing target.
	 */
	function fillComponentSelect(payload) {
		const select = $('extrusion-component-select');
		select.innerHTML = '';
		const none = document.createElement('option');
		none.value = '0';
		none.textContent = T.noTarget;
		select.appendChild(none);
		(payload.components || []).forEach((component) => {
			const option = document.createElement('option');
			option.value = String(component.id);
			option.textContent = component.name + ' [' + component.code + ']';
			select.appendChild(option);
		});
		select.value = String(payload.component || 0);
		$$('.extrusion-detected', select.parentNode).forEach((note) => note.remove());
		if (payload.detected && payload.detected.name) {
			const detected = document.createElement('div');
			detected.className = 'alert alert-info extrusion-detected';
			detected.textContent = T.detected + ' ' + payload.detected.name
				+ ' [' + payload.detected.code + ']';
			select.parentNode.appendChild(detected);
		}
	}

	/**
	 * Fetch the catalogue of one component and re-pair everything by name,
	 * exactly the way the server pairs -- lowercase, name then system name.
	 */
	async function loadCatalogue(componentId) {
		let catalogue;
		try {
			catalogue = await post('extrusionCatalogue', { component_id: componentId });
		} catch (error) {
			catalogue = null;
		}
		state.catalogue = (catalogue && !catalogue.error) ? catalogue : null;
		rematch();
	}

	function matchByName(names, pool) {
		const wanted = names.map((name) => String(name || '').trim().toLowerCase())
			.filter((name) => name !== '');
		for (const row of pool || []) {
			for (const field of ['name', 'system']) {
				const value = String(row[field] || '').trim().toLowerCase();
				if (value !== '' && wanted.indexOf(value) !== -1) {
					return { guid: row.guid, label: row.name || row.system };
				}
			}
		}
		return null;
	}

	/**
	 * Re-draw the proposed pairings from the current catalogue. Explicit
	 * decisions are cleared, because they pointed into another component.
	 */
	function rematch() {
		if (!state.data || !state.data.candidates) {
			return;
		}
		const candidates = state.data.candidates;
		const catalogue = state.catalogue || {};
		(candidates.admin_view || []).forEach((view) => {
			view.match = matchByName([view.label, view.detail], catalogue.admin_views);
			const pool = view.match
				? (catalogue.fields || []).filter((row) => row.view === view.match.guid)
				: (catalogue.fields || []);
			(view.fields || []).forEach((field) => {
				field.match = matchByName([field.label, field.detail], pool);
			});
		});
		(candidates.site_view || []).forEach((candidate) => {
			candidate.match = matchByName([candidate.label], catalogue.site_views);
		});
		(candidates.layout || []).forEach((candidate) => {
			candidate.match = matchByName([candidate.label], catalogue.layouts);
		});
		(candidates.template || []).forEach((candidate) => {
			candidate.match = matchByName([candidate.label], catalogue.templates);
		});
		state.decisions = {};
		state.selected.clear();
	}

	/**
	 * The proposal one candidate arrives with, before any explicit decision.
	 */
	function proposal(candidate) {
		if (candidate.kind === 'power') {
			return candidate.exists
				? { action: 'update', target: candidate.guid, label: candidate.fqn }
				: { action: 'create' };
		}
		return candidate.match
			? { action: 'update', target: candidate.match.guid, label: candidate.match.label }
			: { action: 'create' };
	}

	function decision(candidate) {
		const kind = state.decisions[candidate.kind] || {};
		return kind[candidate.key] || proposal(candidate);
	}

	function decide(candidate, verdict) {
		state.decisions[candidate.kind] = state.decisions[candidate.kind] || {};
		if (verdict === null) {
			delete state.decisions[candidate.kind][candidate.key];
		} else {
			state.decisions[candidate.kind][candidate.key] = verdict;
		}
	}

	function explicit(candidate) {
		const kind = state.decisions[candidate.kind] || {};
		return Object.prototype.hasOwnProperty.call(kind, candidate.key);
	}

	/**
	 * Every candidate on the board, flat, for bulk work and the import.
	 */
	function allCandidates() {
		const rows = [];
		const data = state.data || {};
		const candidates = data.candidates || {};
		(candidates.admin_view || []).forEach((view) => {
			rows.push(view);
			(view.fields || []).forEach((field) => rows.push(field));
		});
		['site_view', 'layout', 'template'].forEach((kind) => {
			(candidates[kind] || []).forEach((candidate) => rows.push(candidate));
		});
		if (data.powers && data.powers.classes) {
			data.powers.classes.forEach((candidate) => rows.push(candidate));
		}
		return rows;
	}

	/**
	 * Render the whole pairing board.
	 */
	function renderBoard() {
		const board = $('extrusion-board');
		const data = state.data || {};
		const candidates = data.candidates || {};
		let html = '';
		if ((candidates.admin_view || []).length) {
			html += kindSection('admin_view', T.adminViews, candidates.admin_view, true);
		}
		['site_view', 'layout', 'template'].forEach((kind) => {
			const label = { site_view: T.siteViews, layout: T.layouts, template: T.templates }[kind];
			if ((candidates[kind] || []).length) {
				html += kindSection(kind, label, candidates[kind], false);
			}
		});
		if (data.powers && (data.powers.classes || []).length) {
			html += powersSection(data.powers);
		}
		board.innerHTML = html;
		refreshBulkBar();
		applyFilter($('extrusion-filter').value);
	}

	function counts(list) {
		let matched = 0;
		(list || []).forEach((candidate) => {
			if (candidate.match || (candidate.kind === 'power' && candidate.exists)) {
				matched++;
			}
		});
		return ' <small>(' + list.length + ' ' + esc(T.items) + ', ' + matched + ' '
			+ esc(T.matched) + ', ' + (list.length - matched) + ' ' + esc(T.newItem) + ')</small>';
	}

	function kindSection(kind, label, list, withFields) {
		let html = '<details class="extrusion-kind" open data-extrusion-kind="' + kind + '">'
			+ '<summary>' + esc(label) + counts(list) + '</summary>'
			+ '<div class="extrusion-rows">';
		list.forEach((candidate) => {
			html += row(candidate);
			if (withFields && (candidate.fields || []).length) {
				html += '<details class="extrusion-fields"><summary>'
					+ esc(T.fields) + counts(candidate.fields) + '</summary>'
					+ '<div class="extrusion-rows">';
				candidate.fields.forEach((field) => {
					html += row(field);
				});
				html += '</div></details>';
			}
		});
		return html + '</div></details>';
	}

	function powersSection(powers) {
		const byLibrary = {};
		(powers.classes || []).forEach((candidate) => {
			byLibrary[candidate.library] = byLibrary[candidate.library] || {};
			const bundle = candidate.bundle || '.';
			byLibrary[candidate.library][bundle] = byLibrary[candidate.library][bundle] || [];
			byLibrary[candidate.library][bundle].push(candidate);
		});
		let html = '<details class="extrusion-kind" open data-extrusion-kind="power">'
			+ '<summary>' + esc(T.powers) + counts(powers.classes) + '</summary>'
			+ '<div class="extrusion-rows">';
		Object.keys(byLibrary).sort().forEach((library) => {
			html += '<details class="extrusion-library" open><summary>' + esc(library) + '</summary>';
			Object.keys(byLibrary[library]).sort().forEach((bundle) => {
				const list = byLibrary[library][bundle];
				html += '<details class="extrusion-bundle" open><summary>' + esc(bundle)
					+ counts(list) + '</summary><div class="extrusion-rows">';
				list.forEach((candidate) => {
					html += row(candidate);
				});
				html += '</div></details>';
			});
			html += '</details>';
		});
		return html + '</div></details>';
	}

	/**
	 * One candidate row: tick, identity, and the three decisions --
	 * create new first, then update with a chosen target, then ignore.
	 */
	function row(candidate) {
		const current = decision(candidate);
		const id = candidate.kind + '|' + candidate.key;
		const isExplicit = explicit(candidate);
		const active = (action) => current.action === action ? ' active' : '';
		const targetLabel = current.action === 'update' && current.label
			? esc(current.label) : esc(T.update);
		return '<div class="extrusion-row' + (isExplicit ? ' explicit' : '')
			+ '" data-extrusion-row="' + esc(id) + '">'
			+ '<label class="extrusion-tick"><input type="checkbox" data-extrusion-check="'
			+ esc(id) + '"' + (state.selected.has(id) ? ' checked' : '') + ' /></label>'
			+ '<span class="extrusion-identity"><b>' + esc(candidate.label) + '</b>'
			+ (candidate.detail && candidate.detail !== candidate.label
				? ' <small>' + esc(candidate.detail) + '</small>' : '')
			+ '</span>'
			+ '<span class="extrusion-actions">'
			+ '<button type="button" class="btn btn-sm extrusion-act' + active('create')
			+ '" data-extrusion-act="create">' + esc(T.createNew) + '</button>'
			+ '<button type="button" class="btn btn-sm extrusion-act extrusion-act-update' + active('update')
			+ '" data-extrusion-act="update" title="' + esc(T.chooseTarget) + '">'
			+ targetLabel + '</button>'
			+ '<button type="button" class="btn btn-sm extrusion-act' + active('ignore')
			+ '" data-extrusion-act="ignore">' + esc(T.ignore) + '</button>'
			+ (isExplicit
				? '<button type="button" class="btn btn-sm extrusion-act extrusion-act-reset" '
					+ 'data-extrusion-act="reset" title="' + esc(T.proposed) + '">&#8634;</button>'
				: '')
			+ '</span></div>';
	}

	function findCandidate(id) {
		const [kind, key] = id.split('|');
		return allCandidates().find(
			(candidate) => candidate.kind === kind && candidate.key === key
		) || null;
	}

	function wireBoard() {
		$('extrusion-board').addEventListener('click', (event) => {
			const button = event.target.closest('[data-extrusion-act]');
			if (button) {
				const rowElement = button.closest('[data-extrusion-row]');
				const candidate = findCandidate(rowElement.dataset.extrusionRow);
				if (!candidate) {
					return;
				}
				const action = button.dataset.extrusionAct;
				if (action === 'create') {
					decide(candidate, { action: 'create' });
				} else if (action === 'ignore') {
					decide(candidate, { action: 'ignore' });
				} else if (action === 'reset') {
					decide(candidate, null);
				} else if (action === 'update') {
					openModal(candidate);
					return;
				}
				renderBoard();
				return;
			}
			const check = event.target.closest('[data-extrusion-check]');
			if (check) {
				const id = check.dataset.extrusionCheck;
				if (check.checked) {
					state.selected.add(id);
				} else {
					state.selected.delete(id);
				}
				refreshBulkBar();
			}
		});
	}

	/**
	 * The one shared searchable picker. A candidate kind draws its choices
	 * from the catalogue pool of that kind, so even eight hundred powers
	 * stay one filtered list instead of eight hundred select fields.
	 */
	function modalPool(kind) {
		const catalogue = state.catalogue || {};
		const pools = {
			admin_view: catalogue.admin_views,
			field: catalogue.fields,
			site_view: catalogue.site_views,
			layout: catalogue.layouts,
			template: catalogue.templates,
			power: catalogue.powers
		};
		return (pools[kind] || []).map((row) => ({
			guid: row.guid,
			label: row.name || row.system || row.guid,
			detail: row.system && row.system !== row.name
				? row.system : (row.namespace || '')
		}));
	}

	function openModal(candidate) {
		state.modal = candidate;
		const search = $('extrusion-modal-search');
		search.value = '';
		renderModalList('');
		$('extrusion-modal').style.display = 'flex';
		search.focus();
	}

	function renderModalList(filter) {
		const list = $('extrusion-modal-list');
		const wanted = filter.trim().toLowerCase();
		const pool = state.modal ? modalPool(state.modal.kind) : [];
		const rows = pool.filter((row) => wanted === ''
			|| row.label.toLowerCase().indexOf(wanted) !== -1
			|| String(row.detail).toLowerCase().indexOf(wanted) !== -1).slice(0, 100);
		if (!rows.length) {
			list.innerHTML = '<div class="extrusion-modal-empty">' + esc(T.noMatches) + '</div>';
			return;
		}
		list.innerHTML = rows.map((row) => '<button type="button" class="extrusion-modal-row" '
			+ 'data-extrusion-target="' + esc(row.guid) + '" data-extrusion-label="' + esc(row.label) + '">'
			+ '<b>' + esc(row.label) + '</b>'
			+ (row.detail ? ' <small>' + esc(row.detail) + '</small>' : '')
			+ '</button>').join('');
	}

	function closeModal() {
		state.modal = null;
		$('extrusion-modal').style.display = 'none';
	}

	/**
	 * Bulk work over the ticked rows.
	 */
	function bulk(action) {
		state.selected.forEach((id) => {
			const candidate = findCandidate(id);
			if (!candidate) {
				return;
			}
			if (action === 'reset') {
				decide(candidate, null);
			} else {
				decide(candidate, { action: action });
			}
		});
		renderBoard();
	}

	function refreshBulkBar() {
		$('extrusion-selected-count').textContent = String(state.selected.size);
	}

	/**
	 * Hide rows the filter does not name, and open every group that still
	 * holds a match -- a hit inside a collapsed group is no hit at all.
	 */
	function applyFilter(value) {
		const wanted = value.trim().toLowerCase();
		$$('#extrusion-board .extrusion-row').forEach((rowElement) => {
			const match = wanted === ''
				|| rowElement.textContent.toLowerCase().indexOf(wanted) !== -1;
			rowElement.style.display = match ? '' : 'none';
			if (match && wanted !== '') {
				let group = rowElement.closest('details');
				while (group) {
					group.open = true;
					group = group.parentElement
						? group.parentElement.closest('details') : null;
				}
			}
		});
	}

	/**
	 * Only what a person decided -- or what pairing proposed as an update --
	 * travels as a verdict. An untouched new candidate keeps the engine's
	 * own identity, so a re-run stays deterministic.
	 */
	function buildDecisions() {
		const payload = {};
		allCandidates().forEach((candidate) => {
			const current = decision(candidate);
			const wouldUpdate = candidate.kind === 'power'
				? candidate.exists : Boolean(candidate.match);
			let verdict = null;
			if (current.action === 'ignore') {
				verdict = { action: 'ignore' };
			} else if (current.action === 'update'
				&& (candidate.kind !== 'power' || current.target !== candidate.guid)) {
				verdict = { action: 'update', target: current.target };
			} else if (current.action === 'create' && wouldUpdate) {
				verdict = { action: 'create' };
			}
			if (verdict) {
				payload[candidate.kind] = payload[candidate.kind] || {};
				payload[candidate.kind][candidate.key] = verdict;
			}
		});
		return payload;
	}

	/**
	 * Run the import under the decisions of the board.
	 */
	async function runImport() {
		const config = Object.assign({}, state.config);
		const select = $('extrusion-component-select');
		config.component = parseInt(select.value, 10) || 0;
		config.detect = false;
		$('extrusion-running-title').textContent = config.path || T.theSource;
		$('extrusion-running-verb').textContent = T.importing;
		showPane('running');
		let payload;
		try {
			payload = await post('extrusionImport', {
				config: JSON.stringify(config),
				decisions: JSON.stringify(buildDecisions())
			});
		} catch (error) {
			payload = { error: error.message || T.requestFailed };
		}
		enableTab('results');
		renderResults(payload || { error: T.importFailed });
		showPane('results');
	}

	/**
	 * The live report: what was written, skipped, and failed, and every
	 * message the engines had to say.
	 */
	function renderResults(payload) {
		const container = $('extrusion-results');
		let html = '';
		if (payload.error) {
			html += '<div class="alert alert-danger">' + esc(payload.error) + '</div>';
		} else {
			html += '<div class="alert alert-success">' + esc(payload.success || T.importDone) + '</div>';
		}
		const report = payload.report || {};
		if (report.dry_run) {
			html += '<div class="alert alert-info">' + esc(T.dryRun) + '</div>';
		}
		const levels = { success: 'success', info: 'info', warning: 'warning', error: 'danger' };
		const messages = payload.messages || {};
		let messageHtml = '';
		Object.keys(levels).forEach((level) => {
			(messages[level] || []).forEach((entry) => {
				messageHtml += '<div class="alert alert-' + levels[level] + '">'
					+ esc(entry.message)
					+ (entry.subject ? ' <small>' + esc(entry.subject) + '</small>' : '')
					+ '</div>';
			});
		});
		if (messageHtml) {
			html += '<h4>' + esc(T.messages) + '</h4>' + messageHtml;
		}
		['written', 'skipped', 'failed'].forEach((section) => {
			const tree = report[section];
			if (!tree || typeof tree !== 'object') {
				return;
			}
			const rows = flatten(tree, section);
			if (!rows.length) {
				return;
			}
			const label = { written: T.written, skipped: T.skipped, failed: T.failed }[section];
			html += '<details' + (section === 'failed' ? ' open' : '') + '><summary>'
				+ esc(label) + ' <small>(' + rows.length + ')</small></summary>'
				+ '<table class="table table-sm"><tbody>'
				+ rows.map((entry) => '<tr><td>' + esc(entry.path) + '</td><td>'
					+ esc(entry.value) + '</td></tr>').join('')
				+ '</tbody></table></details>';
		});
		container.innerHTML = html;
	}

	function flatten(tree, prefix) {
		const rows = [];
		Object.entries(tree).forEach(([key, value]) => {
			const path = prefix + '.' + key;
			if (value && typeof value === 'object') {
				rows.push(...flatten(value, path));
			} else {
				rows.push({ path: path, value: String(value) });
			}
		});
		return rows;
	}

	/**
	 * Wire the page once it stands.
	 */
	document.addEventListener('DOMContentLoaded', () => {
		if (!$('extrusion-page')) {
			return;
		}
		E = window.JCBExtrusion || E;
		T = E.text || {};
		$('extrusion-harvest-button').addEventListener('click', harvest);
		wireBoard();
		$('extrusion-back-button').addEventListener('click', () => showPane('setup'));
		const importButton = $('extrusion-import-button');
		if (importButton) {
			importButton.addEventListener('click', runImport);
		}
		$$('#extrusion-tabs .nav-link').forEach((tab) => {
			tab.addEventListener('click', () => {
				if (!tab.disabled) {
					showPane(tab.dataset.extrusionTab);
				}
			});
		});
		$('extrusion-component-select').addEventListener('change', async (event) => {
			await loadCatalogue(parseInt(event.target.value, 10) || 0);
			renderBoard();
		});
		$$('#extrusion-bulk-bar [data-extrusion-bulk]').forEach((button) => {
			button.addEventListener('click', () => bulk(button.dataset.extrusionBulk));
		});
		$('extrusion-filter').addEventListener('input', (event) => applyFilter(event.target.value));
		$('extrusion-modal-close').addEventListener('click', closeModal);
		$('extrusion-modal-search').addEventListener('input',
			(event) => renderModalList(event.target.value));
		$('extrusion-modal-list').addEventListener('click', (event) => {
			const target = event.target.closest('[data-extrusion-target]');
			if (target && state.modal) {
				decide(state.modal, {
					action: 'update',
					target: target.dataset.extrusionTarget,
					label: target.dataset.extrusionLabel
				});
				closeModal();
				renderBoard();
			}
		});
		$('extrusion-modal').addEventListener('click', (event) => {
			if (event.target === $('extrusion-modal')) {
				closeModal();
			}
		});
		// the loading dots of the running pane
		$$('.loading-dots').forEach((dots) => {
			let x = 0;
			setInterval(() => {
				dots.textContent = '.'.repeat((x++ % 8) || 1);
			}, 500);
		});
	});
})();
