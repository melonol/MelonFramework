<?php

define( 'IN_MELON', true );

class Melon {
	
	static protected $_melon;

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
		self::$_melon->includePath = array( __DIR__ );
		self::$_melon->loaderPermission = new \Melon\File\LoaderPermission( self::$_melon->includePath );
		
		spl_autoload_register( '\Melon::autoload' );
		
		define( 'MELON_INIT', true );
	}

	final static public function load( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本文件，请检查它是否存在" );
		}
		self::_load( $load['source'], $load['target'] );
	}
	
	final static public function autoLoad( $class ) {
		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		foreach( self::$_melon->includePath as $path ) {
			$script = realpath( $path . DIRECTORY_SEPARATOR . $file );
			if( $script ) {
				self::_load( \Melon\File\PathTrace::getSourceFile(), $script );
				return;
			}
		}
		throw new \Melon\Exception\RuntimeException( "无法找到class {$class}所在的脚本文件" );
	}
	
	static private function _load( $source, $target ) {
		$loaded = self::$_melon->loaderSet->has( $target );
		if( ! $loaded && ! is_file( $target ) ) {
			throw new \Melon\Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			throw new \Melon\Exception\RuntimeException( "{$source}脚本没有权限载入{$target}脚本文件" );
		}
		if( ! $loaded ) {
			include $target;
			self::$_melon->loaderSet->set( $target, true );
		}
	}
	
	final static public function acquire( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本，请检查脚本文件是否存在" );
		}
		if( ! is_file( $load['target'] ) ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "{$script}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $load['source'], $load['target'] ) ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "{$load['source']}脚本文件没有权限载入{$load['target']}" );
		}
		return ( include $load['target'] );
	}
	
	final static public function read(  ) {
	}
	
	static public function env() {
		print_r( self::$_melon->loaderSet );
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	final static public function run() {
		
	}
}

Melon::init();
class cms extends Melon {
	static public function init() {
		parent::init();
		print_r( self::$_melon );
	}
}

cms::load( './Melon/Helper/RecursiveSet.php' );