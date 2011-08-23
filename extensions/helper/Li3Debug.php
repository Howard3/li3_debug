<?php

namespace li3_debug\extensions\helper;

use lithium\util\Set;

class Li3Debug extends \lithium\template\Helper {
	public function refCallId($callId, &$data) {
		if (!$callId) {
			return null;
		}
		$method = Set::extract($data, '\[call_id=' . $callId . ']');
		var_dump(compact('method', 'callId'));exit;
	}
}

?>