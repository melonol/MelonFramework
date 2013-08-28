<?php

namespace Melon\Loader;
use Melon\System;


defined( 'IN_MELON' ) or die( 'Permission denied' );

echo System\PathTrace::parse( '.' );

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