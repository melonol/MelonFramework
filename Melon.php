<?php

define( 'IN_MELON', true );

class Melon {
	
	static protected $_loaderSet;
	
	static protected $_permission;
	
	static protected $_includePath = array(
		__DIR__
	);

	static protected $_autoload;

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
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'LoaderPermission.php',
		) );
		
		define( 'MELON_INIT', true );
	}
	
	static private function _initLoader( $autoload ) {
		$scripts = array();
		foreach( $autoload as $script ) {
			require $script;
			$scripts[ $script ] = true;
		}
		self::$_loaderSet = new \Melon\File\LoaderSet( $scripts,
			\Melon\File\LoaderSet::REPLACE_NOT );
		self::$_permission = new \Melon\File\LoaderPermission( self::$_includePath );
		spl_autoload_register( '\Melon::autoload' );
	}

	final static public function load( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本，请检查脚本是否存在" );
		}
		if( ! is_file( $load['target'] ) ) {
			throw new \Melon\Exception\RuntimeException( "{$script}不是一个文件" );
		}
		if( ! self::$_loaderSet->has( $load['target'] ) ) {
			if( ! self::$_permission->verify( $load['source'], $load['target'] ) ) {
				throw new \Melon\Exception\RuntimeException( "{$load['source']}脚本没有权限载入{$load['target']}脚本" );
			}
			include $load['target'];
			self::$_loaderSet->set( $load['target'], true );
		}
	}
	
	final static public function acquire( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本，请检查脚本是否存在" );
		}
		if( ! is_file( $load['target'] ) ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "{$script}不是一个文件" );
		}
		if( ! self::$_permission->verify( $load['source'], $load['target'] ) ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "{$load['source']}脚本没有权限载入{$load['target']}脚本" );
		}
		return ( include $load['target'] );
	}
	
	final static public function read(  ) {
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