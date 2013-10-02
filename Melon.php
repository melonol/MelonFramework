<?php

call_user_func( function() {
	define( 'IN_MELON', true );
	
	require __DIR__ . '/Melon/Exception/BaseException.php';
	require __DIR__ . '/Melon/Exception/RuntimeException.php';
	require __DIR__ . '/Melon/PathTrace.php';
	require __DIR__ . '/Melon/Helper/Set.php';
	require __DIR__ . '/Melon/Helper/RecursiveSet.php';
	require __DIR__ . '/Melon/File/LoaderSet.php';
	spl_autoload_register( '\Melon::autoload' );
} );


class Melon {
	
	const ROOT = __DIR__;
	
	private static $_autoload = null;
	
	final protected function __construct() {
		;
	}

	final public static function load( $file ) {
		return PathTrace::parse( $file, false );
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	final public static function autoLoad( $class ) {
		echo $class;
		exit;
	}
	
	public static function run() {
		print_r( \Melon\PathTrace::getSourceFile() );
	}
}