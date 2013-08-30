<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', true );
define( 'DS', DIRECTORY_SEPARATOR );

const ROOT = __DIR__;

require ROOT . '/Melon/Exception/BaseException.php';
require ROOT . '/Melon/Exception/SourceException.php';
require ROOT . '/Melon/System/PathTrace.php';

class Base {
	
	final protected function __construct() {
		;
	}

	final static public function load( $file ) {
		return System\PathTrace::parse( $file, true, array( 'Melon\Base::load' ) );
	}
	
	final static public function callApp() {
		
	}
	
	final static public function respondApp() {
		
	}
	
	static public function lang() {
		print_r( debug_backtrace() );
	}
	
	static public function cache() {
		
	}
}
