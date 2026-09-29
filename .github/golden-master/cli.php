<?php
/**
 * Run the normal Joomla console with in-memory public-fixture authentication.
 */

require __DIR__ . '/bootstrap.php';

Joomla\CMS\Factory::$application->execute();
