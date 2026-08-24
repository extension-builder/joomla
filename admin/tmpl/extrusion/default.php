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
								</div>
							</div>
						</div>
					</div>
					<div class="p-md-3"><?php if ($this->dankie == 2): ?>
<?php echo LayoutHelper::render('jcbsupportmessage', []); ?><?php else: ?>
<?php echo ComponentbuilderHelper::getDynamicContent('banner', '728-90'); ?><?php endif; ?>
			</div>
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
			<div class="col-md-8 p-md-3">
				<div class="p-md-3"><?php if ($this->dankie == 2): ?>
<?php echo LayoutHelper::render('jcbsupportmessage', []); ?><?php else: ?>
<?php echo ComponentbuilderHelper::getDynamicContent('banner', '728-90'); ?><?php endif; ?>
			</div>
			</div>
		</div>
	</div>

	<div id="extrusion-pane-pairing" class="extrusion-pane" data-extrusion-pane="pairing" style="display:none;">
		<div class="row p-md-3">
			<div class="col-md-8">
				<h3><?php echo Text::_('Pair the harvest with what you already have'); ?></h3>
				<p><?php echo Text::_('Everything below was found in the source. A matched item is set to update its match; everything else is created new. Change any decision -- nothing is written until you import.'); ?></p>
			</div>
			<div class="col-md-4" style="text-align: right;">
				<label for="extrusion-component-select" style="display:block;"><?php echo Text::_('Target component'); ?></label>
				<select id="extrusion-component-select" class="form-select" style="display:inline-block; max-width: 100%;"></select>
			</div>
		</div>
		<div id="extrusion-bulk-bar" class="p-md-2">
			<span id="extrusion-selected-count">0</span> <?php echo Text::_('selected'); ?>:
			<button type="button" class="btn btn-sm btn-outline-primary" data-extrusion-bulk="create"><?php echo Text::_('Create new'); ?></button>
			<button type="button" class="btn btn-sm btn-outline-secondary" data-extrusion-bulk="ignore"><?php echo Text::_('Ignore'); ?></button>
			<button type="button" class="btn btn-sm btn-outline-secondary" data-extrusion-bulk="reset"><?php echo Text::_('Back to proposed'); ?></button>
			<span style="float:right;">
				<input type="text" id="extrusion-filter" class="form-control form-control-sm" style="display:inline-block; width: 260px;"
					placeholder="<?php echo Text::_('Filter the tree'); ?>" />
			</span>
		</div>
		<div id="extrusion-board" class="p-md-2"></div>
		<div class="p-md-3">
			<?php if ($this->canDo->get('extrusion.import')): ?>
				<button type="button" class="btn btn-success btn-lg px-4" id="extrusion-import-button">
					<span class="icon-download icon-white" aria-hidden="true"></span>
					<?php echo Text::_('Import into JCB'); ?>
				</button>
			<?php else: ?>
				<div class="alert alert-info"><?php echo Text::_('You may review this harvest, but you do not have permission to import it.'); ?></div>
			<?php endif; ?>
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
				placeholder="<?php echo Text::_('Type to search'); ?>" autocomplete="off" />
			<div id="extrusion-modal-list" class="extrusion-modal-list"></div>
			<button type="button" class="btn btn-outline-secondary" id="extrusion-modal-close"><?php echo Text::_('Cancel'); ?></button>
		</div>
	</div>
</div>

<script type="text/javascript">
// the extrusion page bootstrap
window.JCBExtrusion = {
	url: '<?php echo $urlAjax; ?>',
	canImport: <?php echo $this->canDo->get('extrusion.import') ? 'true' : 'false'; ?>,
	text: {
		harvesting: '<?php echo Text::_('is being harvested', true); ?>',
		importing: '<?php echo Text::_('is being imported', true); ?>',
		theSource: '<?php echo Text::_('The source', true); ?>',
		harvestFailed: '<?php echo Text::_('The harvest failed', true); ?>',
		importFailed: '<?php echo Text::_('The import failed', true); ?>',
		requestFailed: '<?php echo Text::_('The request could not reach the server. Please try again.', true); ?>',
		needSource: '<?php echo Text::_('Select at least an admin folder, a site folder, or a library folder to harvest.', true); ?>',
		createNew: '<?php echo Text::_('Create new', true); ?>',
		update: '<?php echo Text::_('Update', true); ?>',
		ignore: '<?php echo Text::_('Ignore', true); ?>',
		proposed: '<?php echo Text::_('proposed', true); ?>',
		detected: '<?php echo Text::_('The source was recognised as', true); ?>',
		noTarget: '<?php echo Text::_('None - everything is created new', true); ?>',
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
		catalogueFailed: '<?php echo Text::_('The existing definitions could not be loaded, so nothing could be matched against this component.', true); ?>'
	}
};
</script>
<?php else: ?>
	<h1><?php echo Text::_('No access granted!'); ?></h1>
<?php endif; ?>
