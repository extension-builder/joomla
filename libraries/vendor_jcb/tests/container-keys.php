<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Container keys that are asked for and never registered, and that were already
 * that way before any of this refactor began.
 *
 * bin/check-container-keys.php fails on anything not listed here, so this file
 * is what keeps it useful: it blocks a new unregistered key without demanding
 * that this work go and change code it has no business changing.
 *
 * Each entry says where it was last touched, so it stays obvious that it is not
 * a consequence of moving anything out of the legacy helpers.
 */

return [
	// Componentbuilder/Power/Service/Gitea.php, last touched in v5.0.1-rc1.
	'Gitea.Utilities.Response',

	// Componentbuilder/Service/Gitea.php, last touched in v5.0.1-rc1.
	'Gitea.Utilities.Uri',

	// Componentbuilder/Power/Service/Github.php, last touched in v5.1.1-beta3.
	'Github.Utilities.Http',
	'Github.Utilities.Response',
	'Github.Utilities.Uri',
];
