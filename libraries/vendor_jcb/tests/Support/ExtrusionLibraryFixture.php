<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


/**
 * A small PHP library tree, shaped the way compiled powers are shipped.
 *
 * The vendor folder name carries the backslash head of every namespace, the
 * folders below src carry the dots, and the classes reference each other so a
 * test can hold the whole linking story to account: a parent that is in the
 * harvest, an interface that already exists as a power, an aliased import, an
 * import from outside the powers world, and a license header to carry over.
 *
 * @since  6.1.7
 */
final class ExtrusionLibraryFixture
{
	/**
	 * The license header every fixture class opens with.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	public const LICENSE = "/**\n * @package    Demo.Library\n *\n * @copyright  Copyright (C) 2026 Demo. All rights reserved.\n * @license    GNU General Public License version 2 or later; see LICENSE.txt\n */";

	/**
	 * The relative file map of the library tree.
	 *
	 * @return  array<string, string>  Relative path keyed to file content.
	 * @since   6.1.7
	 */
	public static function files(): array
	{
		return [
			'Demo.Joomla/src/Interfaces/LoaderInterface.php' => <<<'PHP'
<?php
/**
 * @package    Demo.Library
 *
 * @copyright  Copyright (C) 2026 Demo. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Demo\Joomla\Interfaces;


/**
 * Demo Loader Interface
 *
 * @since 1.0.0
 */
interface LoaderInterface
{
	/**
	 * Get the value.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function value(): string;
}
PHP,
			'Demo.Joomla/src/Data/Action/Fetch.php' => <<<'PHP'
<?php
/**
 * @package    Demo.Library
 *
 * @copyright  Copyright (C) 2026 Demo. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Demo\Joomla\Data\Action;


/**
 * Demo Fetch Class
 *
 * @since 1.0.0
 */
abstract class Fetch
{
	/**
	 * Fetch it.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	abstract public function fetch();
}
PHP,
			'Demo.Joomla/src/Data/Loader.php' => <<<'PHP'
<?php
/**
 * @package    Demo.Library
 *
 * @copyright  Copyright (C) 2026 Demo. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Demo\Joomla\Data;


use Demo\Joomla\Interfaces\LoaderInterface;
use Demo\Joomla\Data\Action\Fetch as Getter;
use Joomla\CMS\Factory;


/**
 * Demo Loader Class
 *
 * @since 1.0.0
 */
final class Loader extends Getter implements LoaderInterface
{
	/**
	 * Fetch it.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	public function fetch()
	{
		return Factory::getDate();
	}

	/**
	 * Get the value.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function value(): string
	{
		return 'demo';
	}
}
PHP,
			'Demo.Joomla/src/readme.txt' => 'Not a PHP file at all.',
			'Demo.Joomla/src/helper.php' => "<?php\nfunction demo_helper() { return 1; }\n"
		];
	}
}
