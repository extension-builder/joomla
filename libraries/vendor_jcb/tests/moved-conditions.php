<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Conditions that read differently after moving, and why they still decide the same.
 *
 * The compiler is a listener: a method that finds nothing it can act on quietly
 * produces nothing. Moving a method out of a legacy helper must not change what
 * makes it act, so bin/check-moved-conditions.php pairs every condition that
 * leaves a legacy helper with one that arrives in the classes. A pair that does
 * not match textually is listed here with the reason it is still the same
 * decision. A version branch collapsing into a class per target, and the
 * selector that replaces it, need no entry.
 *
 * Keys are the condition with whitespace, comments and the route to a service
 * removed, exactly as the guard reports it.
 *
 * @return  array{left: array<string,string>, arrived: array<string,string>}
 * @since   1.0.0
 */
return [
	// conditions that left a legacy helper and are written differently now
	'left' => [
		'if(isset($this->lastupdateURL))'
			=> 'the dynamic property became a declared ?string, which is null until set, so the class reads it as !== null',
		"if('onContentAfterTitle'===\$plugin_event)"
			=> 'the variable was renamed to $pluginEvent when the method moved',
		"elseif('onContentBeforeDisplay'===\$plugin_event)"
			=> 'the variable was renamed to $pluginEvent when the method moved',
		"elseif('onContentAfterDisplay'===\$plugin_event)"
			=> 'the variable was renamed to $pluginEvent when the method moved',
		'if(($items=S->get($nameListCode))!==null)'
			=> 'the whole body sat inside this guard, so it became an early return on === null',
		'if(($tabs=S->get($name_single))!==null'
			=> 'the variable was renamed to $nameSingle when the method moved',
		'if($addNewButon>0)'
			=> 'the whole body sat inside this guard, so it became an early return on <= 0',
		'if(isset($this->customAdminViewListLink[$nameListCode])'
			=> 'the array became a Registry, and its get() returns null for a key that was never set',
		'if(isset($this->listColnrBuilder[$nameListCode]))'
			=> 'the array became a Registry, and its get() returns null for a key that was never set',
		'if((isset($this->eximportView[$nameListCode])'
			=> 'the array became a Registry, so isset() && truthy is the truthiness of get()',
		'||!$isExport)'
			=> 'second half of the eximport guard above, which folded onto one line',
		'&&(!in_array($nameSingleCode,$this->setRouterHelpDone)))'
			=> 'the property was renamed to $done once the class no longer needed the concern in the name',
		'if(isset($this->validationFixBuilder[$nameSingleCode])'
			=> 'the fixes array became a Registry, so the isset half of the guard is now the truthiness of get(), and ArrayHelper::check leads the if',
		"if((\$_custom=\$this->setCustomAdminSubMenu("
			=> 'the method moved into the class beside its caller and lost the set prefix it no longer needs',
		'if(isset($this->lastCustomSubMenu)'
			=> 'the property the custom sub menus held their deferred entries in became what takeDeferred() returns, so the isset half is the return itself',
		'unset($this->lastCustomSubMenu);'
			=> 'taking the entries is what clears them now, so there is nothing left to unset once they have been read',
		'if(isset($this->lastCustomMainMenu)'
			=> 'the property the custom main menus held their deferred entries in became what takeDeferred() returns, so the isset half is the return itself',
		'unset($this->lastCustomMainMenu);'
			=> 'taking the entries is what clears them now, so there is nothing left to unset once they have been read',
		"&&S->get('build.dashboard',null)===null)"
			=> 'the joomla_version half chose the class, and the dashboard half became the extension point that class overrides',
		"\$isJoomla3=(S->get('joomla_version',3)==3);"
			=> 'the version the whole method read from became the class the provider chose',
		'if(!$isJoomla3)'
			=> 'what a task that cannot run answers with became the failed() extension point',
		'if($isJoomla3)'
			=> 'how the ajax model is asked for became the ajaxModel() extension point',
		'if(isset($this->viewScriptBuilder[$view])'
			=> 'the scripts array became a Registry, and its get() returns null for a view that was given no script',
		'&&isset($this->viewScriptBuilder[$view][$type]))'
			=> 'second half of the guard above: one Registry path carries both keys, so one read answers both',
		'if(!$this->setLayoutOverride($nameSingleCode,$layoutName,$items))'
			=> 'the helper was renamed to setOverride once the class no longer needed the concern in the name',
		"if(\$parent_key==='guid'&&S->get('joomla_version',3)>4)"
			=> 'the version half chose the class, and the $parent_key half became an early return on !== guid',
		'if($view[\'settings\']->main_get->pagination==1)'
			=> 'the guard was written once in each arm of a version branch, and the collapsed class writes it once',
		'(strlen((string)$leftside)>2||strlen((string)$rightside)>2))'
			=> 'evaluated twice on values that cannot change between the two points, so it is read once into $hasSides',
		'elseif(3==$footable_version)'
			=> 'the branch above it was a version branch, so this became the leading if',
		'if($current_version)'
			=> 'the variable was renamed to $currentVersion when the method moved',
		'if(($data=$this->getLayoutOverride($nameSingleCode,$layoutName))'
			=> 'the helper was renamed to getOverride once the class no longer needed the concern in the name',
		'if(($_customTabHTML=$this->addCustomTabs('
			=> 'the tabs are built once into a registry now, so this reads what addCustomTabs used to return',
		'if(S->exists($nameSingleCode))'
			=> 'the whole body sat inside this guard, so it became an early return on !exists',
		'if(S->exists($nameListCode))'
			=> 'the whole body sat inside this guard, so it became an early return on !exists',
		'if(S->set($addTrashHelper,$nameListCode))'
			=> 'the guard was written once in each of two sibling view bodies, and the shared class writes it once',
		'if(CFactory::_("Compiler.Builder.Items.Method.{$action_}.String")->exists($nameSingleCode))'
			=> 'the service name built from $action_ became injected registries and a map over the only two values the caller sets',
		"if(CFactory::_('Compiler.Builder.Model.'.ucfirst(\$cryptionType).'.Field')->"
			=> 'the service name built from $cryptionType became injected registries and a map over the cryption types the config carries',
		"if(CFactory::_('Compiler.Builder.Model.'.ucfirst(\$cryptionType).'.Field.Initiator')->"
			=> 'the service name built from $cryptionType became injected registries and a map over the cryption types the config carries',
		"elseif(CFactory::_('Compiler.Builder.Model.'.ucfirst(\$cryptionType).'.Field.Initiator')->"
			=> 'the service name built from $cryptionType became injected registries and a map over the cryption types the config carries',
		'if(isset($this->eximportView[$nameListCode])'
			=> 'the array became a Registry, so isset() && truthy is the truthiness of get()',
		'if(isset($this->importCustomScripts[$nameListCode])'
			=> 'the array became a Registry, so isset() && truthy is the truthiness of get()',
		'if(!isset($this->customAdminAdded[$menu[\'settings\']->code]))'
			=> 'the state is filled earlier in the pipeline, so the shim passes it in as an argument rather than the class reading the registry, and the isset is unchanged',
	],
	// conditions the classes carry that no legacy helper had
	'arrived' => [
		'if($this->lastUpdateUrl!==null)'
			=> 'reads the declared ?string the isset() above used to read',
		"if('onContentAfterTitle'===\$pluginEvent)"
			=> 'the renamed variable of the same check',
		"elseif('onContentBeforeDisplay'===\$pluginEvent)"
			=> 'the renamed variable of the same check',
		"elseif('onContentAfterDisplay'===\$pluginEvent)"
			=> 'the renamed variable of the same check',
		'if(($items=S->get($nameListCode))===null)'
			=> 'the early return that replaced the guard wrapping the whole body',
		'if(($tabs=S->get($nameSingle))!==null'
			=> 'the renamed variable of the same check',
		'if($addNewButon<=0)'
			=> 'the early return that replaced the guard wrapping the whole body',
		'if(($links=S->get($nameListCode))!==null'
			=> 'the Registry read that replaced the isset() on the array it succeeded',
		'if($links!==null)'
			=> 'second use of the value read once above, where the legacy repeated the isset()',
		'if($columns!==null)'
			=> 'the Registry read that replaced the isset() on the array it succeeded',
		'if(S->get($nameListCode)||!$isExport)'
			=> 'the Registry read that replaced isset() && truthy on the array it succeeded',
		'if(!S->exists($nameSingleCode))'
			=> 'the early return that replaced the guard wrapping the whole body',
		'if(!S->exists($nameListCode))'
			=> 'the early return that replaced the guard wrapping the whole body',
		'if(!$this->setOverride($nameSingleCode,$layoutName,$items))'
			=> 'the renamed helper of the same check',
		"if(\$parent_key!=='guid')"
			=> 'the early return that carries the non-version half of the guard it replaced',
		'$hasSides=strlen((string)$leftside)>2'
			=> 'reads once what the legacy evaluated twice on values that cannot change in between',
		'||strlen((string)$rightside)>2;'
			=> 'second half of $hasSides above',
		'if(3==$footable_version)'
			=> 'the same check, promoted to the leading if when the version branch above it collapsed',
		'if($cryptionFields!==null&&$cryptionFields->exists($view))'
			=> 'the dynamic service name became injected registries and a map, and a type with no registry has no fields to encrypt, so it is skipped the way an empty registry is',
		"return\$action==='Eximport'"
			=> 'the dynamic service name became injected registries and a map over the only two values the caller sets',
		'if($customAdminAdded!==null)'
			=> 'lets the caller hand over state the compiler filled earlier, and gates no generated output',
		'if($currentVersion)'
			=> 'the renamed variable of the same check',
		'if(($data=$this->getOverride($nameSingleCode,$layoutName))'
			=> 'the renamed helper of the same check',
		'if(($_customTabHTML=S->get('
			=> 'reads the registry the tabs are built into, where the legacy called the builder and read its return',
		'if(ArrayHelper::check($queued))'
			=> 'shim plumbing that carries the builder queue back onto the legacy public property, and gates no generated output',
		'if($this->itemsMethodString($action_)->exists($nameSingleCode))'
			=> 'the map that replaced the service name built from $action_',
		'if(S->'
			=> 'the map that replaced the service name built from $cryptionType',
		'elseif(S->'
			=> 'the map that replaced the service name built from $cryptionType',
		'if(!S->get($nameListCode))'
			=> 'the whole body sat inside the eximport guard, so it became an early return on a falsy Registry read',
		'if(S->get($nameListCode))'
			=> 'the Registry read that replaced isset() && truthy on the array it succeeded',
		'&&(!in_array($nameSingleCode,$this->done)))'
			=> 'the renamed property of the same check',
		'if(ArrayHelper::check('
			=> 'the isset half of the validation fix guard became a Registry read, so its ArrayHelper::check half is now the leading if',
		"if((\$_custom=\$this->customAdminSubMenu("
			=> 'the renamed helper of the same check',
		'if(ArrayHelper::check($deferred))'
			=> 'the entries taken from the custom menus, where the legacy read the property it kept them in',
		"if(S->get('build.dashboard',null)!==null)"
			=> 'the early return that carries the dashboard half of the guard it replaced',
		'if(!isset($customAdminAdded[$menu[\'settings\']->code]))'
			=> 'the same isset, on the argument the shim now passes in',
	],
];
