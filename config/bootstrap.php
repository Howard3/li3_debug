<?php

use li3_debug\extensions\DebuggerWrapper;
use lithium\data\Connections;
use lithium\core\Libraries;

Connections::add('Li3Debug', Libraries::get('li3_debug', 'connection') ?: array(
		'type' => 'database',
		'adapter' => 'Sqlite3',
		'database' => dirname(__DIR__) . '/resources/li3debug.db'
	));

DebuggerWrapper::register();

require __DIR__ . '/media.php';
require __DIR__ . '/routes.php';

?>