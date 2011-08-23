<?php

use lithium\net\http\Router;
use lithium\net\http\Response;
use li3_debug\extensions\DebuggerWrapper;

$mimeTypes = array(
	'css' => 'text/css'
);

Router::connect('/li3_debug', array(
		'library' => 'li3_debug',
		'controller' => 'panel',
		'action' => 'index'
	), array('persist' => array('library')));

Router::connect('/li3_debug/web/{:file:.*}', array(), function($request) use ($mimeTypes) {
		DebuggerWrapper::dontRecord();
		$file = dirname(__DIR__) . '/webdocs/' . $request->file;
		if (!file_exists($file) || is_dir($file)) {
			echo '<h1>404</h1> file not found';
			exit;
		}
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		$type = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'text/plain';
		return new lithium\action\Response(array(
				'headers' => array('Content-type' => $type),
				'body' => file_get_contents($file)
			));
	});

Router::connect('/li3_debug.{:controller}/{:action}/{:id:[0-9a-f]{24}}.ajax', array(
		'library' => 'li3_debug',
		'type' => 'li3d-ajax',
		'id' => null
	), array(
		'persist' => array('library')
	));

Router::connect('/li3_debug.{:controller}/{:action}/{:id:[0-9a-f]{24}}', array(
		'library' => 'li3_debug',
		'id' => null
	), array('persist' => array('library')));

?>