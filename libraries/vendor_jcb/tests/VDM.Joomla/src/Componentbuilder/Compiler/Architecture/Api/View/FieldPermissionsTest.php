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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\View\FieldPermissions;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The field permission guards of the JSON API views.
 *
 * @since 6.1.7
 */
#[CoversClass(FieldPermissions::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FieldPermissionsTest extends ArchitectureTestCase
{
	/**
	 * The item guards of a view with access and view field permissions.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM = <<<'GEN'


		// Get user object.
		$user = Factory::getApplication()->getIdentity();
		$id = is_object($item) ? (int) $item->id : 0;

		// Remove the secret value based on Access access controls.
		if (($id != 0 && !$user->authorise('demo.access.secret', 'com_demo.demo.' . $id))
			|| ($id == 0 && !$user->authorise('demo.access.secret', 'com_demo')))
		{
			$this->fieldsToRenderItem = array_values(array_diff($this->fieldsToRenderItem, ['secret']));
		}

		// Remove the notes value based on View access controls.
		if (($id != 0 && !$user->authorise('demo.view.notes', 'com_demo.demo.' . $id))
			|| ($id == 0 && !$user->authorise('demo.view.notes', 'com_demo')))
		{
			$this->fieldsToRenderItem = array_values(array_diff($this->fieldsToRenderItem, ['notes']));
		}
GEN;

	/**
	 * The list guards of the same view.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIST = <<<'GEN'

		// Get user object.
		$user = Factory::getApplication()->getIdentity();

		// Remove the secret value based on Access access controls.
		if (!$user->authorise('demo.access.secret', 'com_demo'))
		{
			$this->fieldsToRenderList = array_values(array_diff($this->fieldsToRenderList, ['secret']));
		}

		// Remove the notes value based on View access controls.
		if (!$user->authorise('demo.view.notes', 'com_demo'))
		{
			$this->fieldsToRenderList = array_values(array_diff($this->fieldsToRenderList, ['notes']));
		}
GEN;

	/**
	 * A view without field permissions guards nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutFieldPermissionsGuardsNothing(): void
	{
		$subject = $this->renderer(FieldPermissions::class);

		$this->assertSame('', $subject->get('demo'));
		$this->assertSame('', $subject->get('demo', false));
	}

	/**
	 * The item guards check the record and the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheItemGuardsCheckTheRecordAndTheComponent(): void
	{
		$this->assertSame(self::EXPECTED_ITEM, $this->subject()->get('demo'));
	}

	/**
	 * The list guards check the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListGuardsCheckTheComponent(): void
	{
		$this->assertSame(self::EXPECTED_LIST, $this->subject()->get('demo', false));
	}

	/**
	 * An edit permission does not hide a field.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEditPermissionDoesNotHideAField(): void
	{
		$code = $this->subject()->get('demo');

		$this->assertStringNotContainsString('title', $code);
		$this->assertStringNotContainsString('demo.edit.', $code);
	}

	/**
	 * A view whose fields carry the three permission kinds.
	 *
	 * @return  FieldPermissions
	 * @since   6.1.7
	 */
	private function subject(): FieldPermissions
	{
		$fields = new PermissionFields();
		$fields->set('demo.secret.access', 'text');
		$fields->set('demo.notes.view', 'textarea');
		$fields->set('demo.title.edit', 'text');
		$fields->set('other.hidden.view', 'text');

		return $this->renderer(FieldPermissions::class, ['permissionfields' => $fields]);
	}
}
