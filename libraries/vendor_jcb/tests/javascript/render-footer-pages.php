<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once dirname(__DIR__) . '/bootstrap.php';

if (!defined('JDEBUG'))
{
	define('JDEBUG', false);
}

$fixture = new VDM\Tests\Support\CompilerFooterBrowserFixture('pages');
echo json_encode($fixture->pages(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
