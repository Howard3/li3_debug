<?php

namespace li3_debug\extensions\helper;

class Li3DebugHtml extends \lithium\template\helper\Html {
	public function script($path, array $options = array()) {
		if (is_array($path)) {
			foreach ($path as &$entry) {
				$entry = '/li3_debug/web/js/' . $entry;
			}
		}
		return parent::script($path, $options);
	}

	public function style($path, array $options = array()) {
		if (is_array($path)) {
			foreach ($path as &$entry) {
				$entry = '/li3_debug/web/css/' . $entry;
			}
		}
		return parent::style($path, $options);
	}
}

?>