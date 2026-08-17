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
	],
];
