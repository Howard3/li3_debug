<?php

namespace li3_debug\extensions;

use lithium\core\Libraries;
use li3_debug\extensions\FileStreamPersist;
use lithium\util\String;
use lithium\util\Set;
use lithium\analysis\Debugger as Li3Debugger;

class Debugger extends \lithium\core\StaticObject {

	protected static $_classes = array(
		'file_stream_wrapper' => 'li3_debug\extensions\FileStreamWrapper',
		'Li3Debugger' => 'lithium\analysis\Debugger'
	);

	protected static $_regex = array();

	protected static $_persist = array();

	protected static $_model = 'li3_debug\models\Li3Debug';

	protected static $_startTime = null;

	protected static $_instanceId = null;

	/**
	 * @var array holds data about recursion depth.
	 */
	protected static $_funcDepth = array();

	protected static $_strings = array(
		'arguments' => 'readArguments(__METHOD__, func_get_args(), \'{:argsNoType}\')',
		'orig_function' => '{:scope} {:static} function {:ref}{:prefix}{:name}({:args}) {',
		'static_call_original_function' => 'self::{:prefix}{:name}({:argsNoType})',
		'_call_original_function' => '$this->{:prefix}{:name}({:argsNoType})',
		'wrap_return' => '{:debugger}::{:rec_return}(__METHOD__, {:function_call})',
		'wrap_and_mock' => '{:function} return {:function_call}; } {:original_function}',
		'debugger_call' => '{:function} {:debugger}::{:arguments};'
	);

	public static function __init() {
		$function = '/(?<function>(?<scope>public|protected|private)\s';
		$function .= '(?<static>static\s){0,1}function\s(?<ref>&){0,1}(?<name>[0-9a-zA-Z_]*)';
		$function .= '\(\s*(?<args>[\$0-9a-zA-Z_,\s=\(\)\'"]+)\)\s+\{)/';
		$replaceArgTypes = '/[a-z0-9_]+\s+(?=\$)/i';
		self::$_regex = compact('function', 'replaceArgTypes');
		self::$_model = Libraries::get('li3_debug', 'model') ?: self::$_model;
		self::$_startTime = microtime(true);
	}

	public static function register() {
		FileStreamPersist::init();
		$wrapper = static::_instance('file_stream_wrapper');
		stream_wrapper_unregister("file");
		stream_wrapper_register("file", get_class($wrapper));
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
				'args' => null
			);
			$match += $defaults;
			$match['static'] = trim($match['static']);
			$data = array(
				'debugger' => '\\' . __CLASS__,
				'rec_return' => $match['ref'] ? 'RecordReturnRef' : 'RecordReturn',
				'argsNoType' => preg_replace($regex['replaceArgTypes'], '', $match['args']),
				'prefix' => $prefix
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

	public static function readArguments($method, $arguments, $args) {
		$depth = self::_alterDepth($method, 1);
		$args = explode(',', $args);
		array_walk($args, function (&$value) {
				$value = trim($value);
			});
		$args += range(0, count($arguments) - count($args));
		$arguments += array_fill(0, count($args), null);
		$arguments = array_combine($args, $arguments);
		self::_recordAction('call', $method, $depth, $arguments);
	}

	public static function RecordReturn($method, $return) {
		$depth = self::_alterDepth($method, -1);
		self::_recordAction('return', $method, $depth, $return);
		return $return;
	}

	public static function &RecordReturnRef($method, &$return) {
		$depth = self::_alterDepth($method, -1);
		self::_recordAction('return', $method, $depth, $return);
		return $return;
	}

	protected static function _alterDepth($method, $modifier) {
		if (!isset(self::$_funcDepth[$method])) {
			self::$_funcDepth[$method] = $modifier;
			return 0;
		}
		self::$_funcDepth[$method] += $modifier;
		return self::$_funcDepth[$method] - 1;
	}

	/**
	 * This function is temporarily Disabled, causing crashes. 
	 *
	 * @static
	 * @throws ConfigException
	 * @param $action
	 * @param null $method
	 * @param null $depth
	 * @param null $data
	 * @return mixed record id
	 */
	protected static function _recordAction($action, $method = null, $depth = null, $data = null) {
		return;
		$model = self::$_model;
		$data = Li3Debugger::export($data);
		$data = array(
			'instance' => self::$_instanceId,
			'start' => self::$_startTime,
			'current' => microtime(true)
		) + compact('action', 'method', 'depth', 'data');

		$entry = $model::create($data);
		if ($entry->save()) {
			return $entry->{$model::meta('key')};
		}
		throw new ConfigException('Could not save debug data');
	}
}

?>