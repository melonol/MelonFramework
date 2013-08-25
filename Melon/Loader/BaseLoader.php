<?php
defined('IN_MELON') or die('Permission denied');

namespace Melon\Loader;

/**
 * 
 */
abstract class BaseLoader {
	
	abstract public function load();
	
	protected function _debugBacktrace() {
		
	}
	
	protected function _permissionValidation() {
		
	}

}