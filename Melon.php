<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', true );

const ROOT = __DIR__;

require ROOT . '/Melon/Exception/BaseException.php';
require ROOT . '/Melon/Exception/RuntimeException.php';
require ROOT . '/Melon/System/PathTrace.php';

spl_autoload_register('\Melon\Base::autoload');

class Base {
	
	private static $_includePath = array(
		
	);
	
	final protected function __construct() {
		;
	}

	final public static function load( $file ) {
		return System\PathTrace::parse( $file, true );
	}
	
	final public static function callApp() {
		
	}
	
	static public function respondApp() {
		
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	final public static function setIncludePath($path) {
		$this->_includePath[] = $path;
	}
	
	final public static function autoLoad($class) {
//		foreach( $this->_includePath ) {
//			
//		}
	}
	
	public static function run() {
		print_r( System\PathTrace::getSourceFile() );
	}
}

//new Melon\File\Load();