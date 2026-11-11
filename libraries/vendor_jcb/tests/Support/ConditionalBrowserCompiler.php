<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    11th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use Closure;
use Joomla\CMS\Form\Form;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\ViewScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\IfValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\OptionsScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\TargetControlsScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\TargetRelationScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\ValueScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ConditionalRule;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ScriptMediaSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ScriptUserSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ValidationFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewScript as Scripts;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups;
use VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Unique;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Compile real condition artifacts without booting Joomla or resolving Compiler.
 *
 * Filesystem and database collaborators are inert because these fixtures contain
 * only field conditions. This helper feeds the separate executable Node check;
 * the ordinary PHPUnit suite does not need a JavaScript runtime.
 *
 * @since  6.2.1
 */
final class ConditionalBrowserCompiler
{
	/**
	 * Render one normalized view definition through the production generators.
	 *
	 * @param   array   $conditions  Normalized view conditions.
	 * @param   string  $view        The normalized view code.
	 *
	 * @return  array{script:string,footer:string,groups:array}
	 *
	 * @since   6.2.1
	 */
	public function render(array $conditions, string $view = 'parity'): array
	{
		$state = [];
		foreach ([
			[Indent::class, 'indent', "\t"],
			[Indent::class, 'bucket', []],
			[Line::class, 'add', false],
			[Unique::class, 'unique', []],
			[StringHelper::class, 'langTag', 'en-GB'],
		] as [$class, $name, $value])
		{
			$property = new ReflectionProperty($class, $name);
			$state[] = [$property, $property->isInitialized() ? $property->getValue() : $value];
			$property->setValue(null, $value);
		}

		try
		{
			$config = new Config(new Input([]), new Registry(), new Registry());
			$config->set('component_code_name', 'parity');
			$config->set('build_target', 'admin');
			$config->set('joomla_version', 6);
			$config->set('minify', 0);
			$placeholder = new Placeholder($config);
			$groups = (new ReflectionClass(Groups::class))->newInstanceWithoutConstructor();
			$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
			$dispenser->hub = [];
			$users = new ScriptUserSwitch();
			$users->set('user', 'user');
			$fixes = new ValidationFix();
			$scripts = new Scripts();
			$renderer = new ViewScript(
				$config,
				$placeholder,
				$dispenser,
				(new ReflectionClass(Structure::class))->newInstanceWithoutConstructor(),
				new IncludeHelper(),
				(new ReflectionClass(Createdate::class))->newInstanceWithoutConstructor(),
				(new ReflectionClass(Modifieddate::class))->newInstanceWithoutConstructor(),
				new ContentMulti(),
				new ScriptMediaSwitch(),
				$users,
				$fixes,
				$scripts,
				new ValueScript($groups, $users),
				new OptionsScript($groups),
				new IfValueScript($groups, $users),
				new TargetControlsScript($groups, $fixes),
				new TargetRelationScript()
			);
			$renderer->get(['settings' => (object) [
				'name_single_code' => $view,
				'name_list_code' => $view . '_items',
				'conditions' => $conditions,
			]]);

			return [
				'script' => $renderer->script($view, 'fileScript'),
				'footer' => $renderer->script($view, 'footerScript'),
				'groups' => $fixes->getConditions($view),
			];
		}
		finally
		{
			foreach ($state as [$property, $value])
			{
				$property->setValue(null, $value);
			}
		}
	}

	/**
	 * Execute the generated PHP evaluator on the same already normalized values.
	 *
	 * Native filtering, plugins and API PATCH have their separate PHPUnit tests.
	 * This comparison isolates the shared predicate and ordered-action contract.
	 *
	 * @param   array  $groups  The generator's trusted condition groups.
	 * @param   array  $values  Fixture values after any input filtering.
	 *
	 * @return  array<string, bool>  Required attributes decided by the generated rule.
	 *
	 * @throws  \RuntimeException  When the generated rule cannot evaluate its form.
	 * @since   6.2.1
	 */
	public function requirements(array $groups, array $values): array
	{
		if ($groups === [])
		{
			return [];
		}
		$class = __NAMESPACE__ . '\\ConditionalBrowserRule';
		if (!class_exists($class, false))
		{
			$line = new ReflectionProperty(Line::class, 'add');
			$previous = $line->getValue();
			$line->setValue(null, false);
			try
			{
				eval('namespace ' . __NAMESPACE__ . '; use Joomla\\CMS\\Form\\Form; use Joomla\\Registry\\Registry; '
					. 'class ConditionalBrowserRule extends \\Joomla\\CMS\\Form\\FormRule {'
					. (new ConditionalRule())->get() . '}');
			}
			finally
			{
				$line->setValue(null, $previous);
			}
		}

		$fields = [];
		$targets = [];
		foreach ($groups as $group)
		{
			foreach ($group['matches'] as $match)
			{
				$fields[$match['name']] = false;
			}
			foreach ($group['targets'] as $name)
			{
				$targets[$name] = true;
			}
		}
		$fields = array_replace($fields, $targets);
		$xml = '<form>';
		foreach ($fields as $name => $required)
		{
			$xml .= '<field name="' . htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8')
				. '" type="text" filter="raw" required="' . ($required ? 'true' : 'false') . '" />';
		}
		$form = new Form('conditional-browser-parity');
		$form->load($xml . '</form>');
		$rule = new $class();

		try
		{
			if (!$rule->prepare($form, $groups, Closure::fromCallable([$this, 'context']))
				|| !$rule->test($form->getFieldXml('__conditional_required'), null, null, new Registry($values), $form))
			{
				throw new \RuntimeException($rule->getError() ?? 'Generated condition evaluation failed.');
			}
			$required = [];
			foreach (array_keys($targets) as $name)
			{
				$required[$name] = $form->getFieldAttribute($name, 'required') === 'true';
			}

			return $required;
		}
		finally
		{
			$rule->release();
		}
	}

	/**
	 * Resolve the fixture's ordinary request with no stored-record fallback.
	 *
	 * @param   array   $data   Already normalized fixture values.
	 * @param   Form    $form   The exact form owned by the validation.
	 * @param   ?string $group  The selected field group.
	 *
	 * @return  array{stored:array,patch:bool}
	 *
	 * @since   6.2.1
	 */
	public function context(array $data, Form $form, ?string $group): array
	{
		return ['stored' => [], 'patch' => false];
	}
}
