<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper as Html;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use VDM\Component\Componentbuilder\Administrator\Helper\ComponentbuilderHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')->useScript('form.validate');
Html::_('bootstrap.tooltip');

// No direct access to this file
defined('_JEXEC') or die;

// Keep the Joomla main menu visible, as in the maintained custom admin view.
$this->app->getInput()->set('hidemainmenu', false);

// the ajax gateway every extrusion call travels through
$urlAjax = 'index.php?option=com_componentbuilder&format=json&raw=true&'
	. Session::getFormToken() . '=1&task=ajax.';

/**
 * Language note: every user-facing string on this page and in the extrusion
 * JavaScript is a natural string inside Text::_() -- never a language
 * constant, and never added to the language files. JCB detects and manages
 * these strings itself when this code is imported. The JavaScript receives
 * its strings through the map printed below, so the same rule holds there.
 */
?>
<?php if ($this->canDo->get('extrusion.access')): ?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task === 'extrusion.back') {
			parent.history.back();
			return false;
		} else {
			var form = document.getElementById('adminForm');
			form.task.value = task;
			form.submit();
		}
	}
</script>

<div class="main-card p-md-3" id="extrusion-page">

	<ul class="nav nav-tabs" id="extrusion-tabs">
		<li class="nav-item">
			<button type="button" class="nav-link active" id="extrusion-tab-setup" data-extrusion-tab="setup">
				<span class="icon-cog" aria-hidden="true"></span>
				<?php echo Text::_('Setup'); ?>
			</button>
		</li>
		<li class="nav-item">
			<button type="button" class="nav-link" id="extrusion-tab-pairing" data-extrusion-tab="pairing" disabled>
				<span class="icon-shuffle" aria-hidden="true"></span>
				<?php echo Text::_('Pairing'); ?>
			</button>
		</li>
		<li class="nav-item">
			<button type="button" class="nav-link" id="extrusion-tab-results" data-extrusion-tab="results" disabled>
				<span class="icon-list" aria-hidden="true"></span>
				<?php echo Text::_('Results'); ?>
			</button>
		</li>
	</ul>

	<div id="extrusion-pane-setup" class="extrusion-pane" data-extrusion-pane="setup">
		<form action="<?php echo Route::_('index.php?option=com_componentbuilder&view=extrusion'); ?>"
			method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">
			<div class="row">
				<div class="col-md-5 p-md-3">
					<h3><?php echo Text::_('Pull an existing extension into JCB'); ?></h3>
					<p><?php echo Text::_('Select the folders of a Joomla component, or any library of PHP classes, straight from this site. The tool discovers everything inside them on its own, including the install SQL, shows you exactly what it found, and lets you decide item by item what becomes new, what updates something you already have, and what stays out.'); ?></p>
					<?php if ($this->form): ?>
						<?php echo $this->form->renderFieldset('source'); ?>
					<?php endif; ?>
					<button type="button" class="btn btn-primary btn-lg px-4" style="width: 100%;" id="extrusion-harvest-button">
						<span class="icon-search icon-white" aria-hidden="true"></span>
						<?php echo Text::_('Harvest the source'); ?>
					</button>
					<div id="extrusion-setup-notice" class="alert alert-danger mt-2" style="display:none;"></div>
				</div>
				<div class="col-md-7 p-md-3">
					<div class="accordion" id="extrusion-switches">
						<div class="accordion-item">
							<h2 class="accordion-header">
								<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#extrusion-switches-body">
									<?php echo Text::_('What should be harvested'); ?>
								</button>
							</h2>
							<div id="extrusion-switches-body" class="accordion-collapse collapse show" data-bs-parent="#extrusion-switches">
								<div class="accordion-body">
									<?php if ($this->form): ?>
										<?php echo $this->form->renderFieldset('switches'); ?>
										<?php echo $this->form->renderFieldset('advanced'); ?>
									<?php endif; ?>
									<div id="extrusion-namespace-repair-options" hidden>
										<button type="button" class="btn btn-outline-primary" id="extrusion-repair-namespaces-button"
											aria-describedby="extrusion-namespace-repair-description">
											<span class="icon-refresh" aria-hidden="true"></span>
											<?php echo Text::_('Repair Existing Power Namespaces'); ?>
										</button>
										<p id="extrusion-namespace-repair-description" class="mt-2">
											<?php echo Text::_('Select an existing target component in Update mode and its library source folders. Review and repair namespace placeholders for matched Powers only; their GUIDs, code, settings, and links are retained.'); ?>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="p-md-3"><?php if ($this->dankie == 2): ?>
					<?php echo LayoutHelper::render('jcbsupportmessage', []); ?><?php else: ?>
					<?php echo ComponentbuilderHelper::getDynamicContent('banner', '728-90'); ?><?php endif; ?>
				</div>
			</div>
			<input type="hidden" name="task" value="" />
			<?php echo Html::_('form.token'); ?>
		</form>
	</div>

	<div id="extrusion-pane-running" class="extrusion-pane" data-extrusion-pane="running" style="display:none;">
		<div class="row">
			<div class="col-md-4 p-md-3">
				<h3><?php echo $this->escape($this->user->name); ?>, <?php echo Text::_('please wait'); ?></h3>
				<p><b><span id="extrusion-running-title"><?php echo Text::_('The source'); ?></span></b>
					<span id="extrusion-running-verb"><?php echo Text::_('is being harvested'); ?></span>
					<span class="loading-dots">.</span></p>
				<p style="font-size: smaller;"><?php echo Text::_('A large source can carry hundreds of classes and views, so this may take a moment.'); ?></p>
			</div>
		</div>
		<div class="col-md-8 p-md-3">
			<div class="p-md-3"><?php if ($this->dankie == 2): ?>
				<?php echo LayoutHelper::render('jcbsupportmessage', []); ?><?php else: ?>
				<?php echo ComponentbuilderHelper::getDynamicContent('banner', '728-90'); ?><?php endif; ?>
			</div>
		</div>
	</div>

	<div id="extrusion-pane-pairing" class="extrusion-pane" data-extrusion-pane="pairing" style="display:none;">
		<div class="row p-md-3">
			<div class="col-md-8">
				<h3 id="extrusion-pairing-title"><?php echo Text::_('Pair the harvest with what you already have'); ?></h3>
				<p><?php echo Text::_('Everything below was found in the source. Proposals identify the actual target records. Ambiguous or conflicting Powers must be resolved before import. Change any decision -- nothing is written until you approve the current plan.'); ?></p>
				<p id="extrusion-namespace-repair-notice" class="alert alert-info" role="status" hidden>
					<?php echo Text::_('Namespace repair: only the namespaces of existing matched Powers can change. Review the proposed namespace differences; unmatched classes are skipped.'); ?>
				</p>
			</div>
			<div class="col-md-4" style="text-align: right;">
				<label for="extrusion-component-select" style="display:block;"><?php echo Text::_('Target component'); ?></label>
				<select id="extrusion-component-select" class="form-select" style="display:inline-block; max-width: 100%;"></select>
			</div>
		</div>
		<div id="extrusion-bulk-bar" class="p-md-2">
			<div class="extrusion-filters">
				<div class="extrusion-filter-field">
					<label for="extrusion-filter-type"><?php echo Text::_('Entity type'); ?></label>
					<select id="extrusion-filter-type" class="form-select form-select-sm">
						<option value=""><?php echo Text::_('All types'); ?></option>
						<option value="power"><?php echo Text::_('Powers'); ?></option>
						<option value="admin_view"><?php echo Text::_('Admin views'); ?></option>
						<option value="field"><?php echo Text::_('Fields'); ?></option>
						<option value="site_view"><?php echo Text::_('Site views'); ?></option>
						<option value="custom_admin_view"><?php echo Text::_('Custom admin views'); ?></option>
						<option value="layout"><?php echo Text::_('Layouts'); ?></option>
						<option value="template"><?php echo Text::_('Templates'); ?></option>
					</select>
				</div>
				<div class="extrusion-filter-field">
					<label for="extrusion-filter-status"><?php echo Text::_('Matching status'); ?></label>
					<select id="extrusion-filter-status" class="form-select form-select-sm">
						<option value=""><?php echo Text::_('All statuses'); ?></option>
						<option value="matched"><?php echo Text::_('Matched'); ?></option>
						<option value="ambiguous"><?php echo Text::_('Ambiguous'); ?></option>
						<option value="conflict"><?php echo Text::_('Conflict'); ?></option>
						<option value="unresolved"><?php echo Text::_('Unresolved'); ?></option>
						<option value="unmatched"><?php echo Text::_('Unmatched'); ?></option>
						<option value="new"><?php echo Text::_('New'); ?></option>
						<option value="similar"><?php echo Text::_('Similar'); ?></option>
						<option value="shared"><?php echo Text::_('Shared'); ?></option>
						<option value="ignored"><?php echo Text::_('Ignored'); ?></option>
						<option value="filtered"><?php echo Text::_('Filtered'); ?></option>
					</select>
				</div>
				<div class="extrusion-filter-field">
					<label for="extrusion-filter-change"><?php echo Text::_('Planned change'); ?></label>
					<select id="extrusion-filter-change" class="form-select form-select-sm">
						<option value=""><?php echo Text::_('All changes'); ?></option>
						<option value="create"><?php echo Text::_('Create new'); ?></option>
						<option value="update"><?php echo Text::_('Update'); ?></option>
						<option value="nochange"><?php echo Text::_('No change'); ?></option>
						<option value="ignore"><?php echo Text::_('Ignore'); ?></option>
						<option value="blocked"><?php echo Text::_('Blocked'); ?></option>
						<option value="pending"><?php echo Text::_('Pending review'); ?></option>
					</select>
				</div>
				<div class="extrusion-filter-field">
					<label for="extrusion-filter"><?php echo Text::_('Search the tree'); ?></label>
					<input type="text" id="extrusion-filter" class="form-control form-control-sm"
						placeholder="<?php echo Text::_('Filter the tree'); ?>" />
				</div>
			</div>
			<div class="extrusion-bulk-actions">
			<span class="extrusion-filter-count"><span id="extrusion-visible-count">0</span> / <span id="extrusion-total-count">0</span> <?php echo Text::_('items shown'); ?></span>
			<span><span id="extrusion-selected-count">0</span> <?php echo Text::_('selected in this view'); ?>:</span>
			<button type="button" class="btn btn-sm btn-outline-primary" data-extrusion-bulk="create"><?php echo Text::_('Create new'); ?></button>
			<button type="button" class="btn btn-sm btn-outline-secondary" data-extrusion-bulk="ignore"><?php echo Text::_('Ignore'); ?></button>
			<button type="button" class="btn btn-sm btn-outline-secondary" data-extrusion-bulk="reset"><?php echo Text::_('Back to proposed'); ?></button>
			<small><?php echo Text::_('Group selection includes nested items matching these filters.'); ?></small>
			</div>
		</div>
		<div id="extrusion-ambiguity-notice" class="alert alert-warning" hidden>
			<?php echo Text::_('Some items have more than one possible target. Filter them and choose the correct record.'); ?>
			<button type="button" class="btn btn-sm btn-outline-secondary" id="extrusion-show-ambiguous"><?php echo Text::_('Show ambiguous items'); ?></button>
		</div>
		<div id="extrusion-board" class="p-md-2"></div>
		<p id="extrusion-filter-empty" class="p-md-2" role="status" hidden><?php echo Text::_('No items match these filters.'); ?></p>
		<div class="p-md-2">
			<div id="extrusion-review-notice" role="status" aria-live="polite"></div>
		</div>
		<div class="p-md-3">
			<button type="button" class="btn btn-success btn-lg px-4" id="extrusion-import-button" disabled>
				<span class="icon-download icon-white" aria-hidden="true"></span>
				<span id="extrusion-import-label"><?php echo Text::_('Import into JCB'); ?></span>
			</button>
			<button type="button" class="btn btn-outline-secondary btn-lg px-4" id="extrusion-back-button">
				<?php echo Text::_('Back to setup'); ?>
			</button>
		</div>
	</div>

	<div id="extrusion-pane-results" class="extrusion-pane" data-extrusion-pane="results" style="display:none;">
		<div class="row p-md-3">
			<div class="col-md-12">
				<h3 id="extrusion-results-title"><?php echo Text::_('The import report'); ?></h3>
				<div id="extrusion-results"></div>
			</div>
		</div>
	</div>

	<div id="extrusion-folder-modal" class="extrusion-modal" style="display:none;">
		<div class="extrusion-modal-card">
			<h4><?php echo Text::_('Select a folder'); ?></h4>
			<div id="extrusion-folder-path" class="extrusion-folder-path"></div>
			<div id="extrusion-folder-list" class="extrusion-modal-list"></div>
			<div>
				<button type="button" class="btn btn-success" id="extrusion-folder-choose"><?php echo Text::_('Choose this folder'); ?></button>
				<button type="button" class="btn btn-outline-secondary" id="extrusion-folder-close"><?php echo Text::_('Cancel'); ?></button>
			</div>
		</div>
	</div>

	<div id="extrusion-modal" class="extrusion-modal" style="display:none;">
		<div class="extrusion-modal-card">
			<h4 id="extrusion-modal-title"><?php echo Text::_('Choose the target'); ?></h4>
			<input type="text" id="extrusion-modal-search" class="form-control"
				placeholder="<?php echo Text::_('Type to search'); ?>" autocomplete="off" aria-describedby="extrusion-power-search-hint" />
			<p id="extrusion-power-search-hint" hidden><?php echo Text::_('Linked Powers are shown first. To find another Power, enter its exact name, system name, namespace, or GUID.'); ?></p>
			<div id="extrusion-modal-list" class="extrusion-modal-list"></div>
			<button type="button" class="btn btn-outline-secondary" id="extrusion-modal-close"><?php echo Text::_('Cancel'); ?></button>
		</div>
	</div>
	<div id="extrusion-confirm-modal" class="extrusion-modal" role="dialog" aria-modal="true"
		aria-labelledby="extrusion-confirm-title" aria-describedby="extrusion-confirm-description" style="display:none;">
		<div class="extrusion-modal-card">
			<h4 id="extrusion-confirm-title"><?php echo Text::_('Confirm import'); ?></h4>
			<p id="extrusion-confirm-description"><?php echo Text::_('I acknowledge that these changes can affect the system.'); ?></p>
			<p id="extrusion-confirm-dry-run" hidden><?php echo Text::_('This is a dry run. No records will be written.'); ?></p>
			<div class="extrusion-confirm-actions">
				<button type="button" class="btn btn-success" id="extrusion-confirm-import"><?php echo Text::_('Acknowledge and import'); ?></button>
				<button type="button" class="btn btn-outline-secondary" id="extrusion-confirm-cancel"><?php echo Text::_('Cancel'); ?></button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
// the extrusion page bootstrap
window.JCBExtrusion = {
	url: '<?php echo $urlAjax; ?>',
	canImport: true,
	text: {
		pairingTitle: '<?php echo Text::_('Pair the harvest with what you already have', true); ?>',
		repairPairingTitle: '<?php echo Text::_('Review existing Power namespace repairs', true); ?>',
		repairNeedTarget: '<?php echo Text::_('Namespace repair requires Update mode and an explicitly selected existing target component.', true); ?>',
		repairNeedLibraries: '<?php echo Text::_('Select at least one library source folder containing the classes whose Power namespaces should be repaired.', true); ?>',
		repairProposal: '<?php echo Text::_('Proposed namespace repair', true); ?>',
		importLabel: '<?php echo Text::_('Import into JCB', true); ?>',
		repairLabel: '<?php echo Text::_('Apply Namespace Repairs', true); ?>',
		confirmTitle: '<?php echo Text::_('Confirm import', true); ?>',
		repairConfirmTitle: '<?php echo Text::_('Confirm namespace repair', true); ?>',
		confirmDescription: '<?php echo Text::_('I acknowledge that these changes can affect the system.', true); ?>',
		repairConfirmDescription: '<?php echo Text::_('I acknowledge that changing these existing Power namespaces can affect their consumers. Only the reviewed namespace changes will be applied.', true); ?>',
		confirmLabel: '<?php echo Text::_('Acknowledge and import', true); ?>',
		repairConfirmLabel: '<?php echo Text::_('Acknowledge and repair', true); ?>',
		reportTitle: '<?php echo Text::_('The import report', true); ?>',
		repairReportTitle: '<?php echo Text::_('The namespace repair report', true); ?>',
		repairing: '<?php echo Text::_('is having its Power namespaces repaired', true); ?>',
		reviewPending: '<?php echo Text::_('Resolving the current targets and write plan...', true); ?>',
		reviewBlocked: '<?php echo Text::_('Resolve the blocked items before importing.', true); ?>',
		blockerDetails: '<?php echo Text::_('Review conflict details', true); ?>',
		ambiguousHint: '<?php echo Text::_('Choose the correct target from the candidates.', true); ?>',
		selectGroup: '<?php echo Text::_('Select group:', true); ?>',
		selectItem: '<?php echo Text::_('Select item:', true); ?>',
		reviewReady: '<?php echo Text::_('The current targets and effective changes have been validated.', true); ?>',
		actualTarget: '<?php echo Text::_('Actual target', true); ?>',
		newIdentity: '<?php echo Text::_('New Power identity', true); ?>',
		otherCandidates: '<?php echo Text::_('Other candidates', true); ?>',
		relocation: '<?php echo Text::_('Validated namespace relocation', true); ?>',
		skippedExisting: '<?php echo Text::_('Skipped existing (available to dependencies)', true); ?>',
		unresolved: '<?php echo Text::_('unresolved', true); ?>',
		status_matched: '<?php echo Text::_('Matched', true); ?>',
		status_new: '<?php echo Text::_('New', true); ?>',
		status_ambiguous: '<?php echo Text::_('Ambiguous', true); ?>',
		status_conflict: '<?php echo Text::_('Conflict', true); ?>',
		status_unresolved: '<?php echo Text::_('Unresolved', true); ?>',
		status_ignored: '<?php echo Text::_('Ignored', true); ?>',
		status_filtered: '<?php echo Text::_('Filtered', true); ?>',
		status_unmatched: '<?php echo Text::_('Unmatched', true); ?>',
		status_similar: '<?php echo Text::_('Similar', true); ?>',
		status_shared: '<?php echo Text::_('Shared', true); ?>',
		harvesting: '<?php echo Text::_('is being harvested', true); ?>',
		importing: '<?php echo Text::_('is being imported', true); ?>',
		theSource: '<?php echo Text::_('The source', true); ?>',
		harvestFailed: '<?php echo Text::_('The harvest failed', true); ?>',
		importFailed: '<?php echo Text::_('The import failed', true); ?>',
		requestFailed: '<?php echo Text::_('The request failed. Review the current state before trying again.', true); ?>',
		networkFailed: '<?php echo Text::_('The connection failed before a response was received. Check the connection and review the current state before trying again.', true); ?>',
		httpFailed: '<?php echo Text::_('The server returned an unsuccessful response.', true); ?>',
		invalidResponse: '<?php echo Text::_('The server response was not valid JSON for this operation.', true); ?>',
		operationFailed: '<?php echo Text::_('The server could not complete this operation.', true); ?>',
		failureReference: '<?php echo Text::_('Failure reference:', true); ?>',
		powerSearchTruncated: '<?php echo Text::_('Showing the first 100 matches. Refine the search with an exact namespace or GUID.', true); ?>',
		needSource: '<?php echo Text::_('Select at least an admin folder, a site folder, or a library folder to harvest.', true); ?>',
		createNew: '<?php echo Text::_('Create new', true); ?>',
		update: '<?php echo Text::_('Update', true); ?>',
		ignore: '<?php echo Text::_('Ignore', true); ?>',
		proposed: '<?php echo Text::_('proposed', true); ?>',
		detected: '<?php echo Text::_('The source was recognised as', true); ?>',
		noTarget: '<?php echo Text::_('No target component', true); ?>',
		chooseTarget: '<?php echo Text::_('Choose the target', true); ?>',
		noMatches: '<?php echo Text::_('Nothing matches your search', true); ?>',
		adminViews: '<?php echo Text::_('Admin views', true); ?>',
		fields: '<?php echo Text::_('Fields', true); ?>',
		siteViews: '<?php echo Text::_('Site views', true); ?>',
		customAdminViews: '<?php echo Text::_('Custom admin views', true); ?>',
		layouts: '<?php echo Text::_('Layouts', true); ?>',
		templates: '<?php echo Text::_('Templates', true); ?>',
		powers: '<?php echo Text::_('Powers', true); ?>',
		matched: '<?php echo Text::_('matched', true); ?>',
		similar: '<?php echo Text::_('similar', true); ?>',
		newItem: '<?php echo Text::_('new', true); ?>',
		items: '<?php echo Text::_('items', true); ?>',
		written: '<?php echo Text::_('Written', true); ?>',
		skipped: '<?php echo Text::_('Skipped', true); ?>',
		failed: '<?php echo Text::_('Failed', true); ?>',
		dryRun: '<?php echo Text::_('This was a dry run, nothing was written.', true); ?>',
		importDone: '<?php echo Text::_('The import has run', true); ?>',
		harvestAgain: '<?php echo Text::_('Harvest again', true); ?>',
		messages: '<?php echo Text::_('Messages', true); ?>',
		report: '<?php echo Text::_('The full report', true); ?>',
		selectFolder: '<?php echo Text::_('Select', true); ?>',
		addLibrary: '<?php echo Text::_('Add a library folder', true); ?>',
		siteRoot: '<?php echo Text::_('Site root', true); ?>',
		upOneFolder: '<?php echo Text::_('Up one folder', true); ?>',
		emptyFolder: '<?php echo Text::_('This folder holds no folders', true); ?>',
		folderFailed: '<?php echo Text::_('The folder list could not be loaded.', true); ?>',
		catalogueFailed: '<?php echo Text::_('The existing definitions could not be loaded, so nothing could be matched against this component.', true); ?>',
		shared: '<?php echo Text::_('shared', true); ?>',
		sharedWith: '<?php echo Text::_('One field, owned by', true); ?>',
		detach: '<?php echo Text::_('Detach', true); ?>',
		detachHint: '<?php echo Text::_('Decide this view on its own instead of sharing the field', true); ?>',
		detached: '<?php echo Text::_('detached', true); ?>',
		oneField: '<?php echo Text::_('One field linked by', true); ?>',
		views: '<?php echo Text::_('views', true); ?>',
		sharedSection: '<?php echo Text::_('Shared', true); ?>',
		adopted: '<?php echo Text::_('Adopted', true); ?>',
		consolidated: '<?php echo Text::_('Consolidated', true); ?>',
		reused: '<?php echo Text::_('Reused', true); ?>',
		kept: '<?php echo Text::_('Kept', true); ?>',
		noChange: '<?php echo Text::_('no change', true); ?>',
		noChangeHint: '<?php echo Text::_('This record already says what the source says, so the import leaves it alone. Its view is still wired up as it should be.', true); ?>',
		diffHint: '<?php echo Text::_('See exactly what this import would change here', true); ?>',
		diffLoading: '<?php echo Text::_('Reading what would change...', true); ?>',
		weighing: '<?php echo Text::_('weighing...', true); ?>',
		weighingHint: '<?php echo Text::_('This row was decided a moment ago, so what it would change is being read again under the pairing it has now.', true); ?>',
		weighingFailed: '<?php echo Text::_('The board could not be weighed again under its decisions. Open a row to read what it would change now.', true); ?>',
		diffCreates: '<?php echo Text::_('would be created', true); ?>',
		diffUpdates: '<?php echo Text::_('would be updated', true); ?>'
	}
};
</script>
<?php else: ?>
	<h1><?php echo Text::_('No access granted!'); ?></h1>
<?php endif; ?>
