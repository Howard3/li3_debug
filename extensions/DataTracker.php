<?php

namespace li3_debug\extensions;

class DataTracker {
	protected static $_data = array();

	public static function set($path, $data) {
		$parts = explode('/', $path);
		$pointer = &self::$_data;
		foreach ($parts as $part) {
			if (!isset($pointer[$part])) {
				$pointer[$part] = array();
			}
			$pointer = &$pointer[$part];
		}
		$pointer = $data;
	}

	public static function get($path) {
		$parts = explode('/', $path);
		$pointer = self::$_data;
		foreach ($parts as $part) {
			if (!isset($pointer[$part])) {
				return false;
			}
			$pointer = &$pointer[$part];
		}
		return $pointer;
	}
}

?>