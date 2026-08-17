<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Model;


use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\BaseSixFour;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonItem;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonString;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelBasicField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelMediumField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelWhmcsField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelExpertField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelExpertFieldInitiator;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Model\ItemSaveInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Model Item Save Class.
 *
 * Builds the save method of an admin edit view model: the custom code that
 * runs before and after modelling, the JSON fields that are folded back into
 * strings, the permission guards on each guarded item, and the encryption
 * each configured cryption type applies.
 *
 * Only how the current user is reached for a permission check differs
 * between Joomla targets, so that is the extension point the target
 * variants override.
 *
 * @since  6.1.7
 */
class ItemSave implements ItemSaveInterface
{
	/**
	 * The Config Class.
	 *
	 * @var   Config
	 * @since 6.1.7
	 */
	protected Config $config;

	/**
	 * The Placeholder Class.
	 *
	 * @var   Placeholder
	 * @since 6.1.7
	 */
	protected Placeholder $placeholder;

	/**
	 * The Dispenser Class.
	 *
	 * @var   Dispenser
	 * @since 6.1.7
	 */
	protected Dispenser $dispenser;

	/**
	 * The ContentOne Class.
	 *
	 * @var   ContentOne
	 * @since 6.1.7
	 */
	protected ContentOne $contentone;

	/**
	 * The Base Six Four Class.
	 *
	 * @var   BaseSixFour
	 * @since 6.1.7
	 */
	protected BaseSixFour $basesixfour;

	/**
	 * The Json Item Class.
	 *
	 * @var   JsonItem
	 * @since 6.1.7
	 */
	protected JsonItem $jsonitem;

	/**
	 * The Json String Class.
	 *
	 * @var   JsonString
	 * @since 6.1.7
	 */
	protected JsonString $jsonstring;

	/**
	 * The Permission Fields Class.
	 *
	 * @var   PermissionFields
	 * @since 6.1.7
	 */
	protected PermissionFields $permissionfields;

	/**
	 * The Model Basic Field Class.
	 *
	 * @var   ModelBasicField
	 * @since 6.1.7
	 */
	protected ModelBasicField $modelbasicfield;

	/**
	 * The Model Medium Field Class.
	 *
	 * @var   ModelMediumField
	 * @since 6.1.7
	 */
	protected ModelMediumField $modelmediumfield;

	/**
	 * The Model Whmcs Field Class.
	 *
	 * @var   ModelWhmcsField
	 * @since 6.1.7
	 */
	protected ModelWhmcsField $modelwhmcsfield;

	/**
	 * The Model Expert Field Class.
	 *
	 * @var   ModelExpertField
	 * @since 6.1.7
	 */
	protected ModelExpertField $modelexpertfield;

	/**
	 * The Model Expert Field Initiator Class.
	 *
	 * @var   ModelExpertFieldInitiator
	 * @since 6.1.7
	 */
	protected ModelExpertFieldInitiator $modelexpertfieldinitiator;

	/**
	 * Constructor.
	 *
	 * @param Config                     $config                     The Config Class.
	 * @param Placeholder                $placeholder                The Placeholder Class.
	 * @param Dispenser                  $dispenser                  The Dispenser Class.
	 * @param ContentOne                 $contentone                 The ContentOne Class.
	 * @param BaseSixFour                $basesixfour                The Base Six Four Class.
	 * @param JsonItem                   $jsonitem                   The Json Item Class.
	 * @param JsonString                 $jsonstring                 The Json String Class.
	 * @param PermissionFields           $permissionfields           The Permission Fields Class.
	 * @param ModelBasicField            $modelbasicfield            The Model Basic Field Class.
	 * @param ModelMediumField           $modelmediumfield           The Model Medium Field Class.
	 * @param ModelWhmcsField            $modelwhmcsfield            The Model Whmcs Field Class.
	 * @param ModelExpertField           $modelexpertfield           The Model Expert Field Class.
	 * @param ModelExpertFieldInitiator  $modelexpertfieldinitiator  The Model Expert Field Initiator Class.
	 *
	 * @since 6.1.7
	 */
	public function __construct(Config $config,
		Placeholder $placeholder,
		Dispenser $dispenser,
		ContentOne $contentone,
		BaseSixFour $basesixfour,
		JsonItem $jsonitem,
		JsonString $jsonstring,
		PermissionFields $permissionfields,
		ModelBasicField $modelbasicfield,
		ModelMediumField $modelmediumfield,
		ModelWhmcsField $modelwhmcsfield,
		ModelExpertField $modelexpertfield,
		ModelExpertFieldInitiator $modelexpertfieldinitiator)
	{
		$this->config = $config;
		$this->placeholder = $placeholder;
		$this->dispenser = $dispenser;
		$this->contentone = $contentone;
		$this->basesixfour = $basesixfour;
		$this->jsonitem = $jsonitem;
		$this->jsonstring = $jsonstring;
		$this->permissionfields = $permissionfields;
		$this->modelbasicfield = $modelbasicfield;
		$this->modelmediumfield = $modelmediumfield;
		$this->modelwhmcsfield = $modelwhmcsfield;
		$this->modelexpertfield = $modelexpertfield;
		$this->modelexpertfieldinitiator = $modelexpertfieldinitiator;
	}

	/**
	 * Build the save method of an admin edit view model.
	 *
	 * @param   string  $view  The single view code name.
	 *
	 * @return  string
	 *
	 * @since   6.1.7
	 */
	public function get(&$view)
	{
		$script = '';
		// get component name
		$Component = $this->contentone->get('Component');
		$component = $this->config->component_code_name;
		// check if there was script added before modeling of data
		$script .= $this->dispenser->get(
			'php_before_save', $view, PHP_EOL . PHP_EOL
		);
		// turn array into JSON string
		if ($this->jsonitem->exists($view))
		{
			foreach ($this->jsonitem->get($view) as $jsonItem)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Set the " . $jsonItem
					. " items to data.";
				$script .= PHP_EOL . Indent::_(2) . "if (isset(\$data['"
					. $jsonItem . "']) && is_array(\$data['" . $jsonItem
					. "']))";
				$script .= PHP_EOL . Indent::_(2) . "{";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $jsonItem
					. " = new Registry;";
				$script .= PHP_EOL . Indent::_(3) . "\$" . $jsonItem
					. "->loadArray(\$data['" . $jsonItem . "']);";
				$script .= PHP_EOL . Indent::_(3) . "\$data['" . $jsonItem
					. "'] = (string) \$" . $jsonItem . ";";
				$script .= PHP_EOL . Indent::_(2) . "}";
				if ($this->permissionfields->isArray("$view.$jsonItem"))
				{
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						)
						. " Also check permission since the value may be removed due to permissions";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						)
						. " Then we do not want to clear it out, but simple ignore the empty "
						. $jsonItem;
					$script .= PHP_EOL . Indent::_(2)
						. "elseif (!isset(\$data['" . $jsonItem . "'])";
					// only add permission that are available
					foreach ($this->permissionfields->get("$view.$jsonItem")
						as $permission_option => $fieldType
					)
					{
						$script .= $this->getAuthoriseCheck(
							$view, $permission_option, $jsonItem, $component
						);
					}
					$script .= ")";
				}
				else
				{
					$script .= PHP_EOL . Indent::_(2)
						. "elseif (!isset(\$data['" . $jsonItem . "']))";
				}
				$script .= PHP_EOL . Indent::_(2) . "{";
				$script .= PHP_EOL . Indent::_(3) . "//" . Line::_(
						__LINE__,__CLASS__
					) . " Set the empty " . $jsonItem . " to data";
				$script .= PHP_EOL . Indent::_(3) . "\$data['" . $jsonItem
					. "'] = '';";
				$script .= PHP_EOL . Indent::_(2) . "}";
			}
		}
		// turn string into json string
		if ($this->jsonstring->exists($view))
		{
			foreach ($this->jsonstring->get($view) as $jsonString)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Set the " . $jsonString
					. " string to JSON string.";
				$script .= PHP_EOL . Indent::_(2) . "if (isset(\$data['"
					. $jsonString . "']))";
				$script .= PHP_EOL . Indent::_(2) . "{";
				$script .= PHP_EOL . Indent::_(3) . "\$data['" . $jsonString
					. "'] = (string) json_encode(\$data['" . $jsonString
					. "']);";
				$script .= PHP_EOL . Indent::_(2) . "}";
			}
		}
		// turn string into base 64 string
		if ($this->basesixfour->exists($view))
		{
			foreach ($this->basesixfour->get($view) as $baseString)
			{
				$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
					. Line::_(__Line__, __Class__) . " Set the " . $baseString
					. " string to base64 string.";
				$script .= PHP_EOL . Indent::_(2) . "if (isset(\$data['"
					. $baseString . "']))";
				$script .= PHP_EOL . Indent::_(2) . "{";
				$script .= PHP_EOL . Indent::_(3) . "\$data['" . $baseString
					. "'] = base64_encode(\$data['" . $baseString . "']);";
				$script .= PHP_EOL . Indent::_(2) . "}";
			}
		}
		// turn string into encrypted string
		foreach ($this->config->cryption_types as $cryptionType)
		{
			$cryptionFields = $this->cryptionField($cryptionType);
			if ($cryptionFields !== null && $cryptionFields->exists($view))
			{
				if ('expert' !== $cryptionType)
				{
					$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
						. Line::_(__Line__, __Class__) . " Get the " . $cryptionType
						. " encryption key.";
					$script .= PHP_EOL . Indent::_(2) . "\$" . $cryptionType
						. "key = " . $Component . "Helper::getCryptKey('"
						. $cryptionType . "');";
					$script .= PHP_EOL . Indent::_(2) . "//" . Line::_(
							__LINE__,__CLASS__
						) . " Get the encryption object";
					$script .= PHP_EOL . Indent::_(2) . "\$" . $cryptionType
						. " = new Super_" . "__99175f6d_dba8_4086_8a65_5c4ec175e61d___Power(\$" . $cryptionType . "key);";
					foreach ($cryptionFields->get($view) as $baseString)
					{
						$script .= PHP_EOL . PHP_EOL . Indent::_(2) . "//"
							. Line::_(__Line__, __Class__) . " Encrypt data "
							. $baseString . ".";
						$script .= PHP_EOL . Indent::_(2) . "if (isset(\$data['"
							. $baseString . "']) && \$" . $cryptionType
							. "key)";
						$script .= PHP_EOL . Indent::_(2) . "{";
						$script .= PHP_EOL . Indent::_(3) . "\$data['"
							. $baseString . "'] = \$" . $cryptionType
							. "->encryptString(\$data['" . $baseString . "']);";
						$script .= PHP_EOL . Indent::_(2) . "}";
					}
				}
				else
				{
					if ($this->modelexpertfieldinitiator->
						exists("{$view}.save"))
					{
						foreach ($this->modelexpertfieldinitiator->
							get("{$view}.save") as $block)
						{
							$script .= PHP_EOL . Indent::_(2) . implode(
								PHP_EOL . Indent::_(2), $block
							);
						}
					}
					// set the expert script
					foreach ($cryptionFields->get($view) as $baseString => $locker_)
					{
						$_placeholder_for_field
							= array('[[[field]]]' => "\$data['"
							. $baseString . "']");
						$script .= $this->placeholder->update(
							PHP_EOL . Indent::_(2) . implode(
								PHP_EOL . Indent::_(2), $locker_['save']
							), $_placeholder_for_field
						);
					}
				}
			}
		}
		// add custom PHP to the save method
		$script .= $this->dispenser->get(
			'php_save', $view, PHP_EOL . PHP_EOL
		);

		return $script;
	}

	/**
	 * Get the permission check of one guarded json item.
	 *
	 * @param   string  $view               The single view code name.
	 * @param   string  $permission_option  The permission action.
	 * @param   string  $jsonItem           The guarded item.
	 * @param   string  $component          The component code name.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	protected function getAuthoriseCheck($view, $permission_option, $jsonItem, $component): string
	{
		return PHP_EOL . Indent::_(3)
			. "&& Joomla__"."_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->getIdentity()->authorise('" . $view
			. "." . $permission_option . "." . $jsonItem
			. "', 'com_" . $component . "')";
	}

	/**
	 * Get the field registry of one cryption type.
	 *
	 * The legacy helper resolved these by building the service key from the
	 * type name. The compiler ships exactly one registry per configured type,
	 * so they are injected and selected here instead.
	 *
	 * @param   string  $cryptionType  The cryption type.
	 *
	 * @return  Registry|null  The registry, or null when the type carries none.
	 * @since   6.1.7
	 */
	protected function cryptionField(string $cryptionType): ?Registry
	{
		return match (strtolower($cryptionType))
		{
			'basic' => $this->modelbasicfield,
			'medium' => $this->modelmediumfield,
			'whmcs' => $this->modelwhmcsfield,
			'expert' => $this->modelexpertfield,
			// a type with no registry has no fields to encrypt, so the caller
			// skips it, the same way it skips a type whose registry is empty
			default => null,
		};
	}
}
