<?php

namespace li3_debug\extensions;

/**
 * This class persists data for the FileStreamWrapper - the entire purpose of it is because the
 * FileStreamWrapper will be instantiated, wiped, then reinstantiated. This causes data set in it
 * to be wiped, which is useless when attempting to do certain tasks.
 */
class FileStreamPersist {
	public static $data;

	public static function init() {
		self::$data = array(
			'isPHP' => false,
			'fh' => null
		);
	}

	public static function set($name, $value) {
		self::$data[$name] = $value;
	}

	public static function get($name) {
		return isset(self::$data[$name]) ? self::$data[$name] : null;
	}
}

?>