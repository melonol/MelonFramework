<?php

define( 'IN_MELON', true );

class Melon {
	
	static private $_melon;

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
			$melonRoot . 'Util' . DIRECTORY_SEPARATOR . 'Set.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'PathTrace.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'LoaderSet.php',
			$melonRoot . 'File' . DIRECTORY_SEPARATOR . 'LoaderPermission.php',
		);
		
		$scripts = array();
		foreach( $autoload as $script ) {
			require $script;
			$scripts[ $script ] = $script;
		}
		self::$_melon = new \Melon\Util\Set( array(), \Melon\Util\Set::REPLACE_NOT );
		self::$_melon->loaderSet = new \Melon\File\LoaderSet( $scripts,
			\Melon\File\LoaderSet::REPLACE_NOT );
		self::$_melon->includePath = array( __DIR__ );
		//TODO::写到配置文件
		self::$_melon->privatePre = '_';
		self::$_melon->loaderPermission = new \Melon\File\LoaderPermission( self::$_melon->includePath );
		
		spl_autoload_register( '\Melon::autoload' );
		
		define( 'MELON_INIT', true );
	}
	
	
	

	/******************************************************************
	 * 普通加载
	 ******************************************************************/
	
	/**
	 * 载入一个脚本
	 * 
	 * 它可以像require_once一样防止重复载入同一个脚本
	 * 
	 * @param string $script 脚本路径，你可以使用相对路径，程序会自动将其转为绝对路径
	 * @return void
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static public function load( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本文件" );
		}
		self::_load( $load['source'], $load['target'] );
	}
	
	/**
	 * 自动加载类
	 * 
	 * 它被注册到spl_autoload_register函数，所以你不需要手动调用它
	 * 当调用的类不存在时会自动触发
	 * 需要注意的是它是在includePath中查找类文件的，并且以类的命名空间作为目录
	 * 
	 * @param string $class 完整的类名
	 * @return void
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static public function autoLoad( $class ) {
		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		foreach( self::$_melon->includePath as $path ) {
			$script = realpath( $path . DIRECTORY_SEPARATOR . $file );
			if( $script ) {
				self::_load( \Melon\File\PathTrace::sourceFile(), $script );
				return;
			}
		}
		//TODO::输出异常
		throw new \Melon\Exception\RuntimeException( "无法找到class {$class}所在的脚本文件" );
	}
	
	/**
	 * load和autoLoad逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标脚本路径
	 * @return void
	 * @see \Melon::load
	 * @see \Melon::autoLoad
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static private function _load( $source, $target ) {
		$loaded = self::$_melon->loaderSet->has( $target );
		if( ! $loaded && ! is_file( $target ) ) {
			throw new \Melon\Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			throw new \Melon\Exception\RuntimeException( "{$source}脚本文件没有权限载入{$target}" );
		}
		if( ! $loaded ) {
			include $target;
			self::$_melon->loaderSet->set( $target, $target );
		}
	}
	
	/**
	 * 获取载入脚本文件时返回的数据
	 * 
	 * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
	 * 它不会像{@link \Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径
	 * @return mixed
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static public function acquire( $script ) {
		$load = \Melon\File\PathTrace::parse( $script, true );
		if( ! $load ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "无法识别{$script}脚本" );
		}
		return self::_acquire( $load['source'], $load['target'] );
	}
	
	/**
	 * acquire逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标路径
	 * @return mixed
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static private function _acquire( $source, $target ) {
		if( ! is_file( $target ) ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			// TODO::改为抛出警告
			throw new \Melon\Exception\RuntimeException( "{$source}脚本文件没有权限载入{$target}" );
		}
		return ( include $source );
	}
	
	final static public function file() {
	}
	
	/******************************************************************
	 * 包加载
	 ******************************************************************/
	
	/**
	 * 从包中载入一个脚本
	 * 
	 * 和{@link \Melon\load}一样，它也会防止重复载入同一个脚本
	 * 
	 * @param string $script 脚本路径，必需是相对于包的路径
	 * @return void
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static public function packageLoad( $script ) {
		$source = \Melon\File\PathTrace::sourceFile();
		$packageDir = self::_packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new \Melon\Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		self::_load( $source, $target );
	}
	
	/**
	 * 从包中获取载入脚本文件时返回的数据
	 * 
	 * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
	 * 它不会像{@link \Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径，必需是相对于包的路径
	 * @return type
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static public function packageAcquire( $script ) {
		$source = \Melon\File\PathTrace::sourceFile();
		$packageDir = self::_packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new \Melon\Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		return self::_acquire( $source, $target );
	}
	
	/**
	 * 获取当前脚本所在的包的路径
	 * 
	 * @return string 包的路径
	 */
	final static public function packageDir() {
		return self::_packageDir( \Melon\File\PathTrace::sourceFile() );
	}
	
	/**
	 * packageDir逻辑的主要实现
	 * 
	 * @param string $source 载入源路径
	 * @return string 包的路径
	 */
	final static private function _packageDir( $source ) {
		$sourceDir = dirname( $source );
		$parentPos = strrpos( $sourceDir, DIRECTORY_SEPARATOR . self::$_melon->privatePre );
		if( $parentPos ) {
			$spos = ( $parentPos + strlen( DIRECTORY_SEPARATOR ) );
			$epos = strpos( $sourceDir, DIRECTORY_SEPARATOR, $spos );
			if( $epos ) {
				return substr( $sourceDir, 0, $epos );
			}
		}
		return $sourceDir;
	}
	
	static public function env() {
		
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