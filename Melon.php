<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', true );
define( 'DS', DIRECTORY_SEPARATOR );

const ROOT = __DIR__;

require ROOT . '/Melon/Exception/BaseException.php';
require ROOT . '/Melon/Exception/RuntimeException.php';
require ROOT . '/Melon/System/PathTrace.php';

class Base {
	
	final protected function __construct() {
		;
	}

	final static public function load( $file ) {
		return System\PathTrace::parse( $file, true, array( 'Melon\System\PathTrace::parse' ) );
	}
	
	final static public function callApp() {
		
	}
	
	static public function respondApp() {
		
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
}

print_r( System\PathTrace::parse( 'Melon/System/PathTrace.php', true ) );