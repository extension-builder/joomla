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
namespace VDM\Component\Componentbuilder\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

// No direct access to this file
\defined('_JEXEC') or die;

/**
 * Extrusion Admin Controller
 *
 * The extrusion view is a single-page tool: the harvest, the pairing
 * decisions and the import all travel through the AJAX pipeline, so this
 * controller only serves the page and the way back to the dashboard.
 *
 * @since  6.1.7
 */
class ExtrusionController extends AdminController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	protected $text_prefix = 'COM_COMPONENTBUILDER_EXTRUSION';

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
	 *
	 * @since   6.1.7
	 */
	public function getModel($name = 'Extrusion', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Adds option to redirect back to the dashboard.
	 *
	 * @return  void
	 *
	 * @since   6.1.7
	 */
	public function dashboard(): void
	{
		$this->setRedirect(Route::_('index.php?option=com_componentbuilder', false));
	}
}
