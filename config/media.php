<?php

use lithium\net\http\Media;

Media::type('li3d-ajax', array('text/html'), array(
		'view' => 'lithium\template\View',
		'paths' => array(
			'template' => array(
				'{:library}/views/{:controller}/{:template}.ajax.php',
				'{:library}/views/{:controller}/{:template}.html.php'
			),
			'layout' => '{:library}/views/layouts/default.ajax.php'
		),
		'conditions' => array('li3d-ajax' => true)
	));


?>