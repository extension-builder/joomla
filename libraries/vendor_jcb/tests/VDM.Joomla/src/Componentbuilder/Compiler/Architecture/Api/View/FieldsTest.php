<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\View;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\View\Fields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ComponentFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MetaData;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The fields the JSON API views render.
 *
 * @since 6.1.7
 */
#[CoversClass(Fields::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldsTest extends ArchitectureTestCase
{
	private const EXPECTED_DEFAULTS = <<<'GEN'

		'id',
		'created',
		'created_by',
		'modified',
		'modified_by',
		'published',
		'ordering',
		'version',
		'hits',
GEN;

	public function testAViewWithoutFieldsStillRendersTheDefaultColumns(): void
	{
		$subject = $this->renderer(Fields::class);

		$this->assertSame(self::EXPECTED_DEFAULTS, $subject->get('demo'));
	}

	public function testTheViewsOwnFieldsLeadAndEveryTableColumnFollows(): void
	{
		$fields = new ComponentFields();
		$fields->set('demo.name', ['name' => 'name', 'type' => 'text']);
		$fields->set('demo.secret', ['name' => 'secret', 'type' => 'text']);
		$fields->set('demo.hits', ['name' => 'hits', 'type' => 'number']);

		$names = new FieldNames();
		$names->set('demo.hits', 'hits');

		$access = new AccessSwitch();
		$access->set('demo', true);

		$metadata = new MetaData();
		$metadata->set('demo', 'demos');

		$subject = $this->renderer(Fields::class, [
			'componentfields' => $fields,
			'fieldnames' => $names,
			'accessswitch' => $access,
			'metadata' => $metadata,
		]);

		$this->assertSame(
			[
				'id', 'name', 'secret', 'hits', 'created', 'created_by', 'modified',
				'modified_by', 'published', 'ordering', 'access', 'version',
				'metakey', 'metadesc', 'metadata',
			],
			$subject->columns('demo')
		);
	}

	public function testAnotherViewsFieldsAreNotRendered(): void
	{
		$fields = new ComponentFields();
		$fields->set('other.name', ['name' => 'name', 'type' => 'text']);

		$subject = $this->renderer(Fields::class, ['componentfields' => $fields]);

		$this->assertSame(self::EXPECTED_DEFAULTS, $subject->get('demo'));
	}
}
