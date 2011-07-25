<?php

use li3_debug\extensions\Debugger;
use lithium\data\Connections;
use lithium\core\Libraries;
use li3_debug\extensions\FileStreamPersist;

Debugger::register();

Connections::add('Li3Debug', array(
		'type' => 'database',
		'adapter' => 'Sqlite3',
		'database' => Libraries::get('li3_debug', 'database') ? : dirname(__DIR__) .
				'/resources/li3debug.db'
	));

?>