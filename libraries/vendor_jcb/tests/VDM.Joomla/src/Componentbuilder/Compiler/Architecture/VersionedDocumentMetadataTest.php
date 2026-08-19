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
use stdClass;


/**
 * Generated view document metadata contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedDocumentMetadataTest extends ArchitectureTestCase
{
	/**
	 * The metadata of a view reading one item, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_ITEM = <<<'CODE'

		// load the meta description
		if (isset($this->item->metadesc) && $this->item->metadesc)
		{
			$this->setDocumentTitle($this->item->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->setDocumentTitle($this->params->get('menu-meta_description'));
		}
		// load the key words if set
		if (isset($this->item->metakey) && $this->item->metakey)
		{
			$this->getDocument()->setMetadata('keywords', $this->item->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->getDocument()->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// check the robot params
		if (isset($this->item->robots) && $this->item->robots)
		{
			$this->getDocument()->setMetadata('robots', $this->item->robots);
		}
		elseif ($this->params->get('robots'))
		{
			$this->getDocument()->setMetadata('robots', $this->params->get('robots'));
		}
		// check if autor is to be set
		if (isset($this->item->created_by) && $this->params->get('MetaAuthor') == '1')
		{
			$this->getDocument()->setMetaData('author', $this->item->created_by);
		}
		// check if metadata is available
		if (isset($this->item->metadata) && $this->item->metadata)
		{
			$mdata = json_decode($this->item->metadata,true);
			foreach ($mdata as $k => $v)
			{
				if ($v)
				{
					$this->getDocument()->setMetadata($k, $v);
				}
			}
		}
CODE;

	/**
	 * The metadata of a view reading a list, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_LIST = <<<'CODE'

		// load the meta description
		if ($this->params->get('menu-meta_description'))
		{
			$this->getDocument()->setDescription($this->params->get('menu-meta_description'));
		}
		// load the key words if set
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->getDocument()->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// check the robot params
		if ($this->params->get('robots'))
		{
			$this->getDocument()->setMetadata('robots', $this->params->get('robots'));
		}
CODE;

	/**
	 * The metadata of a view read through its own custom get method, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_NAMED = <<<'CODE'

		// load the meta description
		if (isset($this->demo->metadesc) && $this->demo->metadesc)
		{
			$this->setDocumentTitle($this->demo->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->setDocumentTitle($this->params->get('menu-meta_description'));
		}
		// load the key words if set
		if (isset($this->demo->metakey) && $this->demo->metakey)
		{
			$this->getDocument()->setMetadata('keywords', $this->demo->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->getDocument()->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// check the robot params
		if (isset($this->demo->robots) && $this->demo->robots)
		{
			$this->getDocument()->setMetadata('robots', $this->demo->robots);
		}
		elseif ($this->params->get('robots'))
		{
			$this->getDocument()->setMetadata('robots', $this->params->get('robots'));
		}
		// check if autor is to be set
		if (isset($this->demo->created_by) && $this->params->get('MetaAuthor') == '1')
		{
			$this->getDocument()->setMetaData('author', $this->demo->created_by);
		}
		// check if metadata is available
		if (isset($this->demo->metadata) && $this->demo->metadata)
		{
			$mdata = json_decode($this->demo->metadata,true);
			foreach ($mdata as $k => $v)
			{
				if ($v)
				{
					$this->getDocument()->setMetadata($k, $v);
				}
			}
		}
CODE;

	/**
	 * The Joomla 3 metadata of a view reading one item, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_ITEM = <<<'CODE'

		// load the meta description
		if (isset($this->item->metadesc) && $this->item->metadesc)
		{
			$this->document->setDescription($this->item->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		// load the key words if set
		if (isset($this->item->metakey) && $this->item->metakey)
		{
			$this->document->setMetadata('keywords', $this->item->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// check the robot params
		if (isset($this->item->robots) && $this->item->robots)
		{
			$this->document->setMetadata('robots', $this->item->robots);
		}
		elseif ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		// check if autor is to be set
		if (isset($this->item->created_by) && $this->params->get('MetaAuthor') == '1')
		{
			$this->document->setMetaData('author', $this->item->created_by);
		}
		// check if metadata is available
		if (isset($this->item->metadata) && $this->item->metadata)
		{
			$mdata = json_decode($this->item->metadata,true);
			foreach ($mdata as $k => $v)
			{
				if ($v)
				{
					$this->document->setMetadata($k, $v);
				}
			}
		}
CODE;

	/**
	 * The Joomla 3 metadata of a view reading a list, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_LIST = <<<'CODE'

		// load the meta description
		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		// load the key words if set
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// check the robot params
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
CODE;

	/**
	 * The Joomla 3 metadata of a view read through its own custom get method, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_NAMED = <<<'CODE'

		// load the meta description
		if (isset($this->demo->metadesc) && $this->demo->metadesc)
		{
			$this->document->setDescription($this->demo->metadesc);
		}
		elseif ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		// load the key words if set
		if (isset($this->demo->metakey) && $this->demo->metakey)
		{
			$this->document->setMetadata('keywords', $this->demo->metakey);
		}
		elseif ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		// check the robot params
		if (isset($this->demo->robots) && $this->demo->robots)
		{
			$this->document->setMetadata('robots', $this->demo->robots);
		}
		elseif ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		// check if autor is to be set
		if (isset($this->demo->created_by) && $this->params->get('MetaAuthor') == '1')
		{
			$this->document->setMetaData('author', $this->demo->created_by);
		}
		// check if metadata is available
		if (isset($this->demo->metadata) && $this->demo->metadata)
		{
			$mdata = json_decode($this->demo->metadata,true);
			foreach ($mdata as $k => $v)
			{
				if ($v)
				{
					$this->document->setMetadata($k, $v);
				}
			}
		}
CODE;

	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree'],
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * The targets that reach their document through getDocument().
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
	 * Build the document metadata renderer of a target.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version): object
	{
		return $this->renderer(
			$this->targetClass($version, 'View\\DocumentMetadata', ['JoomlaThree']),
			[]
		);
	}

	/**
	 * Build a view definition.
	 *
	 * @param   int         $gettype    What the main get method returns.
	 * @param   int|null    $metadata   Whether the view asks for metadata.
	 * @param   array|null  $customGet  The custom get methods of the view.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(int $gettype, ?int $metadata = 1, ?array $customGet = null): array
	{
		$settings = new stdClass();
		$settings->main_get = (object) ['gettype' => $gettype];
		$settings->code = 'demo';
		$settings->Code = 'Demo';

		if ($customGet !== null)
		{
			$settings->custom_get = $customGet;
		}

		$view = ['settings' => $settings];

		if ($metadata !== null)
		{
			$view['metadata'] = $metadata;
		}

		return $view;
	}

	/**
	 * A view that never asked for metadata is given none.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewThatAsksForNoMetadataIsGivenNone(string $version): void
	{
		$subject = $this->subject($version);
		$item = $this->view(1, null);
		$off = $this->view(1, 0);

		$this->assertSame('', $subject->get($item));
		$this->assertSame('', $subject->get($off));
	}

	/**
	 * A view that reads one item takes its metadata from that item.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAViewReadingOneItemTakesItsMetadataFromIt(string $version): void
	{
		$view = $this->view(1);

		$this->assertSame(self::EXPECTED_MODERN_ITEM, $this->subject($version)->get($view));
	}

	/**
	 * Joomla 3 reaches its document through a property of the view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeReachesItsDocumentThroughAProperty(): void
	{
		$view = $this->view(1);

		$this->assertSame(self::EXPECTED_J3_ITEM, $this->subject('JoomlaThree')->get($view));
	}

	/**
	 * A view that reads a list has only the menu parameters to go on.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAViewReadingAListHasOnlyTheMenuParameters(string $version): void
	{
		$view = $this->view(2);

		$this->assertSame(self::EXPECTED_MODERN_LIST, $this->subject($version)->get($view));
	}

	/**
	 * Joomla 3 reads a list through the same property.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeReadsAListThroughTheSameProperty(): void
	{
		$view = $this->view(2);

		$this->assertSame(self::EXPECTED_J3_LIST, $this->subject('JoomlaThree')->get($view));
	}

	/**
	 * A view whose own custom get method carries its name reads the item from
	 * the property that method fills.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testACustomGetNamedAfterTheViewIsReadAsAnItem(string $version): void
	{
		$view = $this->view(2, 1, [(object) ['getcustom' => 'getDemo']]);

		$this->assertSame(self::EXPECTED_MODERN_NAMED, $this->subject($version)->get($view));
	}

	/**
	 * Joomla 3 reads the same view from the same property.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeReadsACustomGetNamedAfterTheViewAsAnItem(): void
	{
		$view = $this->view(2, 1, [(object) ['getcustom' => 'getDemo']]);

		$this->assertSame(self::EXPECTED_J3_NAMED, $this->subject('JoomlaThree')->get($view));
	}

	/**
	 * A view whose custom get methods carry other names is read as a list.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testCustomGetsNamedAfterSomethingElseAreReadAsAList(string $version): void
	{
		$view = $this->view(2, 1, [(object) ['getcustom' => 'getOther']]);

		$this->assertSame(self::EXPECTED_MODERN_LIST, $this->subject($version)->get($view));
	}

	/**
	 * A view that declares no custom get methods at all is read as a list.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAViewWithNoCustomGetsIsReadAsAList(string $version): void
	{
		$view = $this->view(2, 1, []);

		$this->assertSame(self::EXPECTED_MODERN_LIST, $this->subject($version)->get($view));
	}
}
