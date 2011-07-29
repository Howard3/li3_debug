<?php

use li3_debug\extensions\DebuggerWrapper;
use lithium\data\Connections;
use lithium\core\Libraries;

DebuggerWrapper::register();

Connections::add('Li3Debug', array(
		'type' => 'database',
		'adapter' => 'Sqlite3',
		'database' => Libraries::get('li3_debug', 'database') ? : dirname(__DIR__) .
				'/resources/li3debug.db'
	));

?>