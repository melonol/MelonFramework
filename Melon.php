<?php

define( 'IN_MELON', true );

class Melon {
	
	static protected $_loaderSet;

	private $_autoload = array(
	);

	final protected function __construct() {
		;
	}
	
	static public function init() {
		if( defined( 'MELON_INIT' ) ) {
			return;
		}
		
		$melonRoot = __DIR__ . DIRECTORY_SEPARATOR . 'Melon' . DIRECTORY_SEPARATOR;
		static::_initLoader( array(
			$melonRoot . 'Exception' . DIRECTORY_SEPARATOR . 'BaseException.php',
			$melonRoot . 'Exception' . DIRECTORY_SEPARATOR . 'RuntimeException.php',
			$melonRoot . 'Helper' . DIRECTORY_SEPARATOR . 'Set.php',
			$melonRoot . 'Helper' . DIRECTORY_SEPARATOR . 'RecursiveSet.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'PathTrace.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'LoaderSet.php',
		) );
		
		define( 'MELON_INIT', true );
	}
	
	static private function _initLoader( $autoload ) {
		self::$_loaderSet = new \Melon\File\LoaderSet( $loads,
			\Melon\File\LoaderSet::REPLACE_NOT );
		foreach( $autoload as $script ) {
			require $script;
			self::$_loaderSet->set( $script, true );
		}
		spl_autoload_register( '\Melon::autoload' );
	}

	final static public function load( $file ) {
		return PathTrace::parse( $file, false );
	}
	
	final static public function autoLoad( $class ) {
		echo $class;
		exit;
	}
	
	static public function env() {
		print_r( self::$_loaderSet );
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	static public function run() {
		print_r( \Melon\File\PathTrace::getSourceFile() );
	}
}

class cms extends Melon {
	static public function init() {
		parent::init();
	}
}
Melon::init();
cms::init();