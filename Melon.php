<?php

define( 'IN_MELON', true );

class Melon {
	
	static protected $_melon;
	
	static protected $_includePath = array(
		__DIR__
	);

	final protected function __construct() {
		;
	}
	
	static public function init() {
		if( defined( 'MELON_INIT' ) ) {
			return;
		}
		
		$melonRoot = __DIR__ . DIRECTORY_SEPARATOR . 'Melon' . DIRECTORY_SEPARATOR;
		$autoload = array(
			$melonRoot . 'Exception' . DIRECTORY_SEPARATOR . 'BaseException.php',
			$melonRoot . 'Exception' . DIRECTORY_SEPARATOR . 'RuntimeException.php',
			$melonRoot . 'Helper' . DIRECTORY_SEPARATOR . 'Set.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'PathTrace.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'LoaderSet.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'LoaderPermission.php',
		);
		
		$scripts = array();
		foreach( $autoload as $script ) {
			require $script;
			$scripts[ $script ] = true;
		}
		self::$_melon = new \Melon\Helper\Set( array(), \Melon\Helper\Set::REPLACE_NOT );
		self::$_melon->loaderSet = new \Melon\File\LoaderSet( $scripts,
			\Melon\File\LoaderSet::REPLACE_NOT );
		self::$_melon->permission = new \Melon\File\LoaderPermission( self::$_includePath );
		
		spl_autoload_register( '\Melon::autoload' );
		
		define( 'MELON_INIT', true );
	}

	final static public function load( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本，请检查脚本是否存在" );
		}
		if( ! is_file( $load['target'] ) ) {
			throw new \Melon\Exception\RuntimeException( "{$script}不是一个文件" );
		}
		if( ! self::$_melon->loaderSet->has( $load['target'] ) ) {
			if( ! self::$_melon->permission->verify( $load['source'], $load['target'] ) ) {
				throw new \Melon\Exception\RuntimeException( "{$load['source']}脚本没有权限载入{$load['target']}脚本" );
			}
			include $load['target'];
			self::$_melon->loaderSet->set( $load['target'], true );
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
		if( ! self::$_melon->permission->verify( $load['source'], $load['target'] ) ) {
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
		print_r( self::$_melon->loaderSet );
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	static public function run() {
		print_r( \Melon\File\PathTrace::getSourceFile() );
	}
}

Melon::init();
class cms extends Melon {
	static public function init() {
		parent::init();
		print_r( self::$_melon );
	}
}
