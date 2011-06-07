<?php

use lithium\action\Dispatcher;
use lithium\util\collection\Filters;
use li3_debug\extensions\DataTracker;
use lithium\util\String;

define('LI3_DEBUG_START', microtime(true));

$applyTimeEvent = function($start, $event) {
	DataTracker::set('timers/' . number_format($start - LI3_DEBUG_START, 6), array(
			'runtime' => number_format(microtime(true) - $start, 6),
			'event' => $event,
			'memory' => memory_get_usage(true)
		));
};

Filters::apply('lithium\action\Dispatcher', '_callable',
	function($self, $params, $chain) use ($applyTimeEvent) {
		if (!isset($params['options']['render'])) {
			$params['options']['render'] = array();
		}
		$params['options']['render'] += array('auto' => false);
		$controller = $chain->next($self, $params, $chain);
		$controller->applyFilter('__invoke',
			function($self, $params, $chain) use ($applyTimeEvent) {
				$start = microtime(true);
				$return = $chain->next($self, $params, $chain);
				$request = &$params['request']->params;
				$library = (isset($request['library']) ? $request['library'] : 'app');
				$action =  $library . '.' . $request['controller'] . '::' . $request['action'];
				$applyTimeEvent($start, $action);
				$start = microtime(true);
				$applyTimeEvent($start, 'render');
				DataTracker::set('renderKey', number_format($start - LI3_DEBUG_START, 6));
				$self->render();
				return $return;
			});
		return $controller;
	});

Filters::apply('lithium\storage\Cache', 'read',
	function($self, $params, $chain) use ($applyTimeEvent) {
		$start = microtime(true);
		$return = $chain->next($self, $params, $chain);
		$applyTimeEvent($start, String::insert('Cache::read({:key})', $params));
		return $return;
	});

Filters::apply('lithium\storage\Cache', 'write',
	function($self, $params, $chain) use ($applyTimeEvent) {
		$start = microtime(true);
		$return = $chain->next($self, $params, $chain);
		$applyTimeEvent($start, String::insert('Cache::write({:key})', $params));
		return $return;
	});
?>