<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\GenerateNewAlias;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\GenerateNewTitle;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAlias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Title;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Generated copy naming and folder moving contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedInstallAndCopyTest extends ArchitectureTestCase
{
	/**
	 * The generate new title method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_NEW_TITLE = <<<'GEN'


	/**
	 * Method to change the title/s & alias.
	 *
	 * @param   string         $alias        The alias.
	 * @param   string/array   $title        The title.
	 *
	 * @return	array/string  Contains the modified title/s and/or alias.
	 *
	 */
	protected function _generateNewTitle($alias, $title = null)
	{

		// Alter the title/s & alias
		$table = $this->getTable();

		while ($table->load(['alias' => $alias]))
		{
			// Check if this is an array of titles
			if (Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($title))
			{
				foreach($title as $nr => &$_title)
				{
					$_title = StringHelper::increment($_title);
				}
			}
			// Make sure we have a title
			elseif ($title)
			{
				$title = StringHelper::increment($title);
			}
			$alias = StringHelper::increment($alias, 'dash');
		}
		// Check if this is an array of titles
		if (Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($title))
		{
			$title[] = $alias;
			return $title;
		}
		// Make sure we have a title
		elseif ($title)
		{
			return array($title, $alias);
		}
		// We only had an alias
		return $alias;
	}
GEN;

	/**
	 * The generate new alias method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_NEW_ALIAS = <<<'GEN'


	/**
	 * Generate a valid alias from title / date.
	 * Remains public to be able to check for duplicated alias before saving
	 *
	 * @return  string
	 */
	public function generateAlias()
	{
		if (empty($this->alias))
		{
			$this->alias = $this->name;
		}

		$this->alias = ApplicationHelper::stringURLSafe($this->alias);

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getDate()->format('Y-m-d-H-i-s');
		}

		return $this->alias;
	}
GEN;

	/**
	 * The folder moving method a modern target writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MOVE_MODERN = <<<'GEN'


	/**
	 * Method to move folders into place.
	 *
	 * @param   InstallerAdapter  $adapter  The adapter calling this method
	 *
	 * @return void
	 * @since 4.4.2
	 */
	protected function moveFolders(InstallerAdapter $adapter): void
	{
		// get the installation path
		$installer = $adapter->getParent();
		$installPath = $installer->getPath('source');
		// get all the folders
		$folders = Folder::folders($installPath);
		// check if we have folders we may want to copy
		$doNotCopy = ['media','admin','site']; // Joomla already deals with these
		if (count((array) $folders) > 1)
		{
			foreach ($folders as $folder)
			{
				// Only copy if not a standard folders
				if (!in_array($folder, $doNotCopy))
				{
					// set the source path
					$src = $installPath.'/'.$folder;
					// set the destination path
					$dest = JPATH_ROOT.'/'.$folder;
					// now try to copy the folder
					if (!Folder::copy($src, $dest, '', true))
					{
						$this->app->enqueueMessage('Could not copy '.$folder.' folder into place, please make sure destination is writable!', 'error');
					}
				}
			}
		}
	}
GEN;

	/**
	 * The folder moving method Joomla 3 writes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MOVE_J3 = <<<'GEN'


	/**
	 * Method to set/copy dynamic folders into place (use with caution)
	 *
	 * @return void
	 */
	protected function setDynamicF0ld3rs($app, $parent)
	{
		// get the installation path
		$installer = $parent->getParent();
		$installPath = $installer->getPath('source');
		// get all the folders
		$folders = Folder::folders($installPath);
		// check if we have folders we may want to copy
		$doNotCopy = ['media','admin','site']; // Joomla already deals with these
		if (count((array) $folders) > 1)
		{
			foreach ($folders as $folder)
			{
				// Only copy if not a standard folders
				if (!in_array($folder, $doNotCopy))
				{
					// set the source path
					$src = $installPath.'/'.$folder;
					// set the destination path
					$dest = JPATH_ROOT.'/'.$folder;
					// now try to copy the folder
					if (!Folder::copy($src, $dest, '', true))
					{
						$app->enqueueMessage('Could not copy '.$folder.' folder into place, please make sure destination is writable!', 'error');
					}
				}
			}
		}
	}
GEN;

	/**
	 * What a view without an alias of its own is given instead, captured from
	 * the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_NEW_ALIAS_NONE = <<<'GEN'


	/**
	 * This view does not actually have an alias
	 *
	 * @return  bool
	 */
	public function generateAlias()
	{
		return false;
	}
GEN;

	/**
	 * The targets whose install script is handed the adapter.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * What the compiler knows about the naming of the demo view.
	 *
	 * @param   bool  $withTitle  Whether the view has a title of its own.
	 * @param   bool  $withAlias  Whether the view has an alias of its own.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function naming(bool $withTitle = true, bool $withAlias = true): array
	{
		$title = new Title();
		if ($withTitle)
		{
			$title->set('demo', 'name');
		}

		$alias = new Alias();
		if ($withAlias)
		{
			$alias->set('demo', 'alias');
		}

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		return [
			'title' => $title,
			'alias' => $alias,
			'customalias' => new CustomAlias(),
			'contentone' => $contentone,
		];
	}

	/**
	 * Build the folder mover of a target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   bool    $moves    Whether the component has folders to move.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function mover(string $version, bool $moves = true): object
	{
		$registry = new Registry();
		if ($moves)
		{
			$registry->set('set_move_folders_install_script', true);
		}

		return $this->renderer(
			$this->targetClass($version, 'Component\\MoveFolderMethod', ['JoomlaThree']),
			['registry' => $registry]
		);
	}

	/**
	 * A view with a title of its own is given the method that makes a copy of
	 * it unique.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithATitleIsGivenTheMethodThatMakesACopyUnique(): void
	{
		$subject = $this->renderer(GenerateNewTitle::class, $this->naming());

		$this->assertSame(self::EXPECTED_NEW_TITLE, $subject->get('demo'));
	}

	/**
	 * A view without a title of its own is given no such method.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutATitleIsGivenNoSuchMethod(): void
	{
		$subject = $this->renderer(GenerateNewTitle::class, $this->naming(false));

		$this->assertSame('', $subject->get('demo'));
	}

	/**
	 * A view with an alias of its own is given the method that makes a copy of
	 * it unique.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAnAliasIsGivenTheMethodThatMakesACopyUnique(): void
	{
		$subject = $this->renderer(GenerateNewAlias::class, $this->naming());

		$this->assertSame(self::EXPECTED_NEW_ALIAS, $subject->get('demo'));
	}

	/**
	 * A view without an alias of its own is given a method that says so, since
	 * the model it belongs to calls one either way.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAnAliasIsGivenAMethodThatSaysSo(): void
	{
		$subject = $this->renderer(GenerateNewAlias::class, $this->naming(true, false));

		$this->assertSame(self::EXPECTED_NEW_ALIAS_NONE, $subject->get('demo'));
	}

	/**
	 * A component with no folders to move is given no method to move them.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAComponentWithNoFoldersToMoveIsGivenNoMethod(string $version): void
	{
		$this->assertSame('', $this->mover($version, false)->get());
	}

	/**
	 * A modern install script is handed the adapter it was called by.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernInstallScriptIsHandedTheAdapter(string $version): void
	{
		$this->assertSame(self::EXPECTED_MOVE_MODERN, $this->mover($version)->get());
	}

	/**
	 * A Joomla 3 install script is handed the application and the parent.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeInstallScriptIsHandedTheApplication(): void
	{
		$this->assertSame(self::EXPECTED_MOVE_J3, $this->mover('JoomlaThree')->get());
	}
}
