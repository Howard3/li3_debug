<?php

use lithium\util\collection\Filters;
use lithium\template\View;
use lithium\core\Libraries;
use lithium\core\Environment;
use li3_debug\extensions\DataTracker;
use lithium\util\Set;

Filters::apply('lithium\net\http\Media', '_handle', function($self, $params, $chain) {
	$output = $chain->next($self, $params, $chain);
	$libraryPath = Libraries::get('li3_debug', 'path');
	$view = new View(array(
		'paths' => array(
			'template' =>  $libraryPath . '/views/debug/{:template}.{:type}.php',
			'layout' => false
		)
	));
	$peakMemory = memory_get_peak_usage(true);
	$timers = DataTracker::get('timers');
	$renderKey = DataTracker::get('renderKey');
	$renderTime = number_format(microtime(true) - ($renderKey + LI3_DEBUG_START), 6);
	$timers[$renderKey]['runtime'] = $renderTime;
	$maxRuntime = 0;
	foreach ($timers as $timer) {
		if ($timer['runtime'] > $maxRuntime) {
			$maxRuntime = $timer['runtime'];
		}
	}
	$timers[number_format(microtime(true) - LI3_DEBUG_START, 6)] = array(
		'runtime' => number_format(microtime(true) - LI3_DEBUG_START, 6),
		'event' => 'application end',
		'memory' => memory_get_usage(true)
	);
	$output .= $view->render('template', compact('timers', 'maxRuntime', 'peakMemory'), array(
			'template' => 'bar',
			'type' => 'html'
		));
	return $output;
});

?>