<?php

namespace li3_debug\extensions;

use li3_debug\extensions\FileStreamPersist;

/**
 * File Stream Wrapper, this class is responsible for intercepting read files so that they can be
 * passed into the debugger and have the appropriate debug calls attached to their functions.
 */
class FileStreamWrapper {
	public function __set($name, $value) {
		FileStreamPersist::set($name, $value);
	}

	public function __get($name) {
		return FileStreamPersist::get($name);
	}

	function stream_open($path, $mode, $options, &$opened_path) {
		$this->isPHP = pathinfo($path, PATHINFO_EXTENSION) === 'php';

		$self = $this;
		$this->fsAction(function() use ($path, $mode, $self) {
				$self->fh = fopen($path, $mode);
				$self->fileSize = !is_dir($path) ? filesize($path) : 0;
				$self->readPos = 0;
			});
		return $this->fh;
	}

	function stream_read($count) {
		if (!$this->readPos) {
			$this->data = fread($this->fh, $this->fileSize < $count ? $count : $this->fileSize);
		}

		if ($this->isPHP && $this->data && !$this->readPos) {
			$this->data = Debugger::attach($this->data);
		}

		$return = substr($this->data, $this->readPos, $count);
		$this->readPos += $count;
		return $return;
	}

	function stream_write($data) {
		return fwrite($this->fh, $data);
	}

	function stream_tell() {
		return ftell($this->fh);
	}

	function stream_eof() {
		return feof($this->fh);
	}

	function stream_seek($offset, $whence) {
		return fseek($this->fh, $offset, $whence);
	}

	function stream_stat() {
		return fstat($this->fh);
	}

	function url_stat($path, $flags) {
		return $this->fsAction(function() use ($path) {
				return file_exists($path) ? lstat($path) : false;
			});
	}

	public function unlink($path) {
		return $this->fsAction(function() use ($path){
				return is_dir($path) ? rmdir($path) : unlink($path);
			});
	}

	function fsAction($closure) {
		if (!stream_wrapper_restore('file')) {
			throw new Exception('Could not restore the file stream.');
		}
		$return = $closure();
		stream_wrapper_unregister('file');
		stream_wrapper_register('file', get_class($this));
		return $return;
	}
}