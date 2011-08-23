<?php

namespace li3_debug\extensions;

use li3_debug\models\DebugStore;

use lithium\core\Libraries;
use lithium\util\String;
use lithium\util\Set;
use lithium\core\ConfigException;

class DebuggerWrapper extends \lithium\core\StaticObject {

	protected static $_classes = array(
		'file_stream_wrapper' => 'li3_debug\extensions\FileStreamWrapper'
	);

	protected static $_regex = array();

	protected static $_model = 'li3_debug\models\DebugStore';

	protected static $_startTime = null;

	protected static $_instanceId = null;

	/**
	 * @var $data Stores data for FileStreamWrapper.
	 */
	public static $data;

	protected static $_strings = array(
		'arguments' => 'readArguments(__METHOD__, func_get_args(), \'{:argsNoType}\', $cid)',
		'call_id' => '$cid = mt_rand();',
		'orig_function' => 'private {:static} function {:ref}{:prefix}{:name}({:args}) {',
		'static_call_original_function' => 'static::{:prefix}{:name}({:argsNoType})',
		'_call_original_function' => '$this->{:prefix}{:name}({:argsNoType})',
		'wrap_return' => '{:debugger}::{:rec_return}(__METHOD__, {:function_call}, $cid)',
		'wrap_and_mock' => '{:function} return {:function_call}; } {:original_function}',
		'debugger_call' => '{:function} {:call_id} {:debugger}::{:arguments};'
	);

	protected static $_ignore = array();

	protected static $_blockRecording = true;

	protected static $_location = array();

	/**
	 * Pull in class files do they're loaded prior to the file stream wrapper. This prevents
	 * infinite loops.
	 * @static
	 * @return void
	 */
	public static function register() {
		$function = '/(?P<function>(?P<_static>static\s){0,1}(?P<scope>public|protected|private)\s';
		$function .= '(?P<static>static\s){0,1}function\s(?P<ref>&){0,1}(?P<name>[0-9a-zA-Z_]*)';
		$function .= '\(\s*(?P<args>[\$0-9a-zA-Z_,\s=\(\)\'"]+)\)\s+\{)/';
		$replaceArgTypes = '/[a-z0-9_]+\s+(?=\$)/i';
		self::$_regex = compact('function', 'replaceArgTypes');
		self::$_model = Libraries::get('li3_debug', 'model') ? : self::$_model;
		self::$_startTime = microtime(true);

		$ignoreDefaults = array(
			'li3_debug\extensions\DebugWrapper',
			self::$_model
		);
		$userIgnoreClasses = Libraries::get('li3_debug', 'ignore');
		self::$_ignore = array_flip($ignoreDefaults) + array_flip((array)$userIgnoreClasses);

		self::$data = array(
			'isPHP' => false,
			'fh' => null
		);
		$wrapper = static::_instance('file_stream_wrapper');
		stream_wrapper_unregister("file");
		stream_wrapper_register("file", get_class($wrapper));

		$page = $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		$debug = DebugStore::create(array(
				'action' => 'start',
				'call_time' => time(),
				'method' => $page
			));
		if (!$debug->save()) {
			throw new ConfigException("li3_debug: Cannot save debug data.");
		}
		self::$_instanceId = (string)$debug->{DebugStore::key()};
		self::$_blockRecording = false;
	}

	/**
	 * Called by the FileStreamWrapper class.
	 * 
	 * This method rewrites class functions to attach recorders to to method arguments and return
	 * results.
	 * @static
	 * @param $data string - file data
	 * @return string
	 */
	public static function attach($data) {
		$strings = self::$_strings;
		$regex = self::$_regex;
		$replaceArray = array('g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p');
		$prefix = str_replace(array_keys($replaceArray), array_values($replaceArray), md5($data));
		$prefix .= '_';
		$data = str_replace("__FUNCTION__", "str_replace('$prefix', '', __FUNCTION__)", $data);
		$data = str_replace("__METHOD__", "str_replace('$prefix', '', __METHOD__)", $data);
		$replace = function($match) use ($strings, $regex, $prefix) {
			$defaults = array(
				'function' => null,
				'scope' => null,
				'static' => null,
				'ref' => null,
				'name' => null,
				'args' => null,
				'_static' => null
			);
			$match += $defaults;
			$match['static'] = trim($match['static'] . $match['_static']);
			$data = array(
				'debugger' => '\\' . __CLASS__,
				'rec_return' => $match['ref'] ? 'RecordReturnRef' : 'RecordReturn',
				'argsNoType' => preg_replace($regex['replaceArgTypes'], '', $match['args']),
				'prefix' => $prefix,
				'call_id' => $strings['call_id']
			) + $match;

			$argsNoType = explode(',', $data['argsNoType']);
			foreach ($argsNoType as &$arg) {
				$parts = explode('=', $arg, 2);
				if (isset($parts[1])) {
					$arg = $parts[0];
				}
				if (substr(trim($arg), 0, 1) != '$') {
					$arg = null;
				}
			}
			$data['argsNoType'] = implode(',', array_filter($argsNoType));

			$data['arguments'] = String::insert($strings['arguments'], $data);
			$data['function'] = String::insert($strings['debugger_call'], $data);
			$function_call = $strings[$match['static'] . '_call_original_function'];
			$data['function_call'] = String::insert($function_call, $data);
			$data['function_call'] = String::insert($strings['wrap_return'], $data);
			$data['original_function'] = String::insert($strings['orig_function'], $data);

			$function = String::insert($strings['wrap_and_mock'], $data);
			return $function;
		};
		$return = preg_replace_callback(self::$_regex['function'], $replace, $data, -1, $count);
		return $return;
	}

	public static function dontRecord() {
		if (!self::$_instanceId) {
			return;
		}
		$model = self::$_model;
		$model::remove(array($model::key() => self::$_instanceId));
		$model::remove(array('instance' => self::$_instanceId));
		self::$_blockRecording = true;
	}

	public static function readArguments($method, $arguments, $args, $callId) {
		$args = explode(',', $args);
		array_walk($args, function (&$value) {
				$value = trim($value);
			});
		self::$_location[$callId] = $method;
		$args += range(0, count($arguments) - count($args));
		$arguments += array_fill(0, count($args), null);
		$arguments = array_combine($args, $arguments);
		self::_recordAction('call', $method, $arguments, $callId);
	}

	public static function RecordReturn($method, $return, $callId) {
		unset(self::$_location[$callId]);
		self::_recordAction('return', $method, $return, $callId);
		return $return;
	}

	public static function &RecordReturnRef($method, &$return, $callId) {
		unset(self::$_location[$callId]);
		self::_recordAction('return', $method, $return, $callId);
		return $return;
	}

	/**
	 * Sets data for FileStreamWrapper
	 * @static
	 * @param $name
	 * @param $value
	 * @return void
	 */
	public static function set($name, $value) {
		self::$data[$name] = $value;
	}

	/**
	 * Gets data for FileStreamWrapper
	 * @static
	 * @param $name
	 * @return null
	 */
	public static function get($name) {
		return isset(self::$data[$name]) ? self::$data[$name] : null;
	}

	/**
	 * @static
	 * @throws \lithium\core\ConfigException
	 * @param $action
	 * @param $method
	 * @param string $data
	 * @param int $id
	 * @return 
	 */
	protected static function _recordAction($action, $method, $data = "", $id = 0) {
		list($class, $classMethod) = explode('::', $method);
		$inIgnoreArray = isset(self::$_ignore[$method]) || isset(self::$_ignore[$class]);
		if (self::$_blockRecording || $inIgnoreArray) {
			return;
		}
		self::$_blockRecording = true;
		$model = self::$_model;

		ob_start();
		var_dump($data);
		$data = ob_get_clean();

		$record = null;
		switch ($action) {
			case 'call':
				end(self::$_location);
				$data = array(
					'instance' => self::$_instanceId,
					'start' => self::$_startTime,
					'call_time' => microtime(true),
					'call_id' => $id,
					'arguments' => $data,
					'called_from' => key(self::$_location),
					'depth' => count(self::$_location)
				) + compact('action', 'method');
				$record = $model::create($data);
				break;
			case 'return':
				$record = $model::first(array(
						'conditions' => array(
							'instance' => self::$_instanceId,
							'call_id' => $id
						)
					));
				if (!$record) {
					return;
				}
				$record->return = $data;
				$record->return_time = microtime(true);
				break;
		}
		if ($record && $record->save()) {
			$recordId = $record->{$model::key()};
			self::$_blockRecording = false;
			return $recordId;
		}
		throw new ConfigException('Could not save debug data');
	}
}

?>