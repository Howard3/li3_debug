<?php

namespace li3_debug\controllers;

use lithium\core\Libraries;
use li3_debug\extensions\DebuggerWrapper;

class PanelController extends \lithium\action\Controller {
	protected $_model = 'li3_debug\models\DebugStore';

	public function _init() {
		parent::_init();
		$this->_render['library'] = 'li3_debug';
		$this->_model = Libraries::get('li3_debug', 'model') ? : $this->_model;
		DebuggerWrapper::dontRecord();
	}

	public function index() {
		$model = $this->_model;
		$records = $model::all(array(
				'conditions' => array(
					'action' => 'start'
				),
				'order' => array('call_time' => 'DESC')
			));
		return compact('records');
	}

	public function view() {
		$model = $this->_model;
		$records = $model::all(array(
				'conditions' => array(
					'instance' => $this->request->id
				),
				'fields' => array('_id', 'call_id', 'call_time', 'method', 'call_id',
					'return_time', 'start', 'called_from', 'depth'),
				'order' => array('call_time' => 'ASC')
			));
		return compact('records');
	}

	public function call() {
		$model = $this->_model;
		$record = $model::first($this->request->id);
		return compact('record');
	}
}

?>