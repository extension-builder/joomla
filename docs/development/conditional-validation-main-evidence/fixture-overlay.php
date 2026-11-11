<?php
require __DIR__ . '/bootstrap.php';
$db = $container->get(Joomla\Database\DatabaseInterface::class);
$component = '3745af8f-f96b-4e17-831e-eb4062cd4389';
$view = '65116558-be67-4931-95be-727fbfb16db7';
$greeting = '75e830a6-a3a5-4327-9161-3f774a6f1591';
$find = static function (string $table, string $guid) use ($db): object
{
	$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__componentbuilder_' . $table))->where('guid=' . $db->quote($guid));
	$record = $db->setQuery($query)->loadObject();
	if (!$record)
	{
		throw new RuntimeException('Missing fixture definition: ' . $table . '/' . $guid);
	}
	return $record;
};
$componentRow = $find('joomla_component', $component);
$viewRow = $find('admin_view', $view);
$template = $find('field', $greeting);
$listType = 'a51dfc06-1b9b-4d0a-86ba-f705bcd40d4d';
$find('fieldtype', $listType);
$fields = [
	'storage_kind' => ['guid' => '64ea45de-6a6f-4f5e-84b6-fd8ce03c0201', 'type' => 'list', 'label' => 'Storage Kind', 'options' => 'row|Database,note|Note,spacer|Spacer', 'default' => 'row'],
	'length_mode' => ['guid' => '64ea45de-6a6f-4f5e-84b6-fd8ce03c0202', 'type' => 'list', 'label' => 'Length Mode', 'options' => 'none|None,fixed|Fixed,custom|Custom', 'default' => 'fixed'],
	'database_type' => ['guid' => '64ea45de-6a6f-4f5e-84b6-fd8ce03c0203', 'type' => 'text', 'label' => 'Database Type', 'default' => ''],
	'database_length' => ['guid' => '64ea45de-6a6f-4f5e-84b6-fd8ce03c0204', 'type' => 'text', 'label' => 'Database Length', 'default' => ''],
];
$db->transactionStart();
try
{
	foreach ($fields as $name => $settings)
	{
		$record = clone $template;
		unset($record->id);
		$record->asset_id = 0;
		$record->guid = $settings['guid'];
		$record->name = 'Conditional Fixture ' . $settings['label'];
		$record->created = '2026-10-07 00:00:00';
		$record->modified = '2026-10-07 00:00:00';
		$record->created_by = 0;
		$record->modified_by = 0;
		if ($settings['type'] === 'list')
		{
			$record->fieldtype = $listType;
		}
		$xml = '<field type="' . $settings['type'] . '" name="' . $name . '" label="' . $settings['label'] . '" required="true" filter="STRING" default="' . $settings['default'] . '"';
		if (isset($settings['options']))
		{
			$xml .= ' multiple="false" option="' . $settings['options'] . '"';
		}
		$record->xml = json_encode($xml . ' />', JSON_THROW_ON_ERROR);
		$query = $db->getQuery(true)->select('id')->from('#__componentbuilder_field')->where('guid=' . $db->quote($record->guid));
		$existing = $db->setQuery($query)->loadResult();
		if ($existing)
		{
			$record->id = (int) $existing;
			$db->updateObject('#__componentbuilder_field', $record, 'id');
		}
		else
		{
			$db->insertObject('#__componentbuilder_field', $record, 'id');
		}
		$fields[$name]['id'] = $record->id;
	}
	$query = $db->getQuery(true)->select('*')->from('#__componentbuilder_admin_fields')->where('admin_view=' . $db->quote($view));
	$links = $db->setQuery($query)->loadObject();
	if (!$links)
	{
		throw new RuntimeException('The public Greeting view must have its native field list.');
	}
	$addfields = json_decode($links->addfields, true, 512, JSON_THROW_ON_ERROR);
	$known = array_column($addfields, 'field');
	foreach ($fields as $name => $settings)
	{
		if (!in_array($settings['guid'], $known, true))
		{
			$addfields[] = ['field' => $settings['guid'], 'list' => '0', 'order_list' => '0', 'filter' => '', 'tab' => '1', 'alignment' => 1, 'order_edit' => (string) (count($addfields) + 1)];
		}
	}
	$links->addfields = json_encode($addfields, JSON_THROW_ON_ERROR);
	$db->updateObject('#__componentbuilder_admin_fields', $links, 'id');
	$conditions = [
		['target_field' => [$fields['database_length']['guid']], 'target_behavior' => 1, 'target_relation' => 1, 'match_field' => $fields['length_mode']['guid'], 'match_behavior' => 1, 'match_options' => "fixed\ncustom"],
		['target_field' => [$fields['database_length']['guid']], 'target_behavior' => 1, 'target_relation' => 1, 'match_field' => $greeting, 'match_behavior' => 4, 'match_options' => ''],
		['target_field' => [$fields['database_type']['guid']], 'target_behavior' => 1, 'target_relation' => 0, 'match_field' => $fields['storage_kind']['guid'], 'match_behavior' => 2, 'match_options' => "note\nspacer"],
		['target_field' => [$fields['database_type']['guid'], $fields['database_length']['guid']], 'target_behavior' => 4, 'target_relation' => 0, 'match_field' => $fields['storage_kind']['guid'], 'match_behavior' => 1, 'match_options' => "note\nspacer"],
	];
	$query = $db->getQuery(true)->select('*')->from('#__componentbuilder_admin_fields_conditions')->where('admin_view=' . $db->quote($view));
	$record = $db->setQuery($query)->loadObject();
	if ($record)
	{
		$record->addconditions = json_encode($conditions, JSON_THROW_ON_ERROR);
		$db->updateObject('#__componentbuilder_admin_fields_conditions', $record, 'id');
	}
	else
	{
		$record = (object) ['admin_view' => $view, 'addconditions' => json_encode($conditions, JSON_THROW_ON_ERROR), 'published' => 1, 'created' => '2026-10-07 00:00:00', 'version' => 1];
		$db->insertObject('#__componentbuilder_admin_fields_conditions', $record, 'id');
	}
	$db->transactionCommit();
}
catch (Throwable $error)
{
	$db->transactionRollback();
	throw $error;
}
$evidence = ['component' => $component, 'source_repository' => '562624ab-48bf-4979-9a14-6b10cf3635de', 'view' => $view, 'purpose' => 'Disposable authored DSL overlay: related selectors, legacy Is Not OR, ordered shared targets, final positive HideOnly applicability.', 'fields' => $fields, 'conditions' => $conditions];
file_put_contents(dirname(__DIR__) . '/evidence/conditional-fixture.json', json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
echo json_encode(['component' => $component, 'view' => $view, 'added_fields' => count($fields), 'authored_condition_rows' => count($conditions)], JSON_THROW_ON_ERROR), "\n";
