<?php

namespace Melon\Loader;
use Melon\System;

defined( 'IN_MELON' ) or die( 'Permission denied' );


print_r( \Melon\Base::load( 'D:\apmserv\www\htdocs\7725\p7725svr\read.txt' ) );

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