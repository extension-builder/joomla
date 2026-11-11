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
use Joomla\CMS\User\User;
use Joomla\DI\Container;
use Joomla\Database\Mysqli\MysqliDriver;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use ReflectionClass;
use ReflectionProperty;
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
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
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
	 * @param   int     $target      Supported Joomla compile target; legacy output by default.
	 *
	 * @return  array{script:string,footer:string,groups:array}
	 *
	 * @throws  \InvalidArgumentException  When the target has no supported renderer.
	 *
	 * @since   6.2.1
	 */
	public function render(array $conditions, string $view = 'parity', int $target = 3): array
	{
		if (!in_array($target, [3, 4, 5, 6], true))
		{
			throw new \InvalidArgumentException('Unsupported Joomla condition-renderer target: ' . $target);
		}

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
			$config->set('joomla_version', $target);
			$config->set('minify', 0);
			$placeholder = new Placeholder($config);
			$groups = (new ReflectionClass(Groups::class))->newInstanceWithoutConstructor();
			$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
			$dispenser->hub = [];
			$users = new ScriptUserSwitch();
			$users->set('user', 'user');
			$media = new ScriptMediaSwitch();
			$media->set('media', 'media');
			$fixes = new ValidationFix();
			$scripts = new Scripts();
			$container = new Container();
			foreach ([
				'Config' => $config,
				'Placeholder' => $placeholder,
				'Customcode.Dispenser' => $dispenser,
				'Utilities.Structure' => (new ReflectionClass(Structure::class))->newInstanceWithoutConstructor(),
				'Library.IncludeHelper' => new IncludeHelper(),
				'Model.Createdate' => (new ReflectionClass(Createdate::class))->newInstanceWithoutConstructor(),
				'Model.Modifieddate' => (new ReflectionClass(Modifieddate::class))->newInstanceWithoutConstructor(),
				'Compiler.Builder.Content.Multi' => new ContentMulti(),
				'Compiler.Builder.Script.Media.Switch' => $media,
				'Compiler.Builder.Script.User.Switch' => $users,
				'Compiler.Builder.Validation.Fix' => $fixes,
				'Compiler.Builder.View.Script' => $scripts,
				'Architecture.Field.ValueScript' => new ValueScript($groups, $users),
				'Architecture.Field.OptionsScript' => new OptionsScript($groups),
				'Architecture.Field.IfValueScript' => new IfValueScript($groups, $users),
				'Architecture.Field.TargetControlsScript' => new TargetControlsScript($groups, $fixes),
				'Architecture.Field.TargetRelationScript' => new TargetRelationScript(),
			] as $alias => $service)
			{
				$container->share($alias, $service, true);
			}
			(new ArchitectureView())->register($container);
			$renderer = $container->get('Architecture.AdminView.ViewScript');
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
	 * @param   array  $groups    The generator's trusted condition groups.
	 * @param   array  $values    Fixture values after any input filtering.
	 * @param   array  $metadata  Explicit native type, required, disabled and multiple field attributes.
	 *
	 * @return  array<string, bool>  Required attributes decided by the generated rule.
	 *
	 * @throws  \RuntimeException  When the generated rule cannot evaluate its form.
	 * @since   6.2.1
	 */
	public function requirements(array $groups, array $values, array $metadata = []): array
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
			$attributes = [
				'name' => $name,
				'type' => $metadata[$name]['type'] ?? 'text',
				'filter' => 'raw',
				'required' => ($metadata[$name]['required'] ?? $required) ? 'true' : 'false',
			];
			foreach (['disabled', 'multiple'] as $attribute)
			{
				if (isset($metadata[$name][$attribute]))
				{
					$attributes[$attribute] = $metadata[$name][$attribute] ? 'true' : 'false';
				}
			}
			$xml .= '<field';
			foreach ($attributes as $attribute => $value)
			{
				$xml .= ' ' . $attribute . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8') . '"';
			}
			$xml .= ' />';
		}
		$form = new Form('conditional-browser-parity');
		// Native field setup needs a typed database dependency but performs no query.
		$form->setDatabase((new ReflectionClass(MysqliDriver::class))->newInstanceWithoutConstructor());
		$form->setCurrentUser(new User());
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
