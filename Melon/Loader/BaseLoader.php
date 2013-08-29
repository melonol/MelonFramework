<?php

namespace Melon\Loader;
use Melon\System;

defined( 'IN_MELON' ) or die( 'Permission denied' );

echo \Melon\ROOT;

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