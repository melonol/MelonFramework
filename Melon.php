<?php

define( 'IN_MELON', true );

use Melon\Base;
use Melon\Exception;
use Melon\Http;
use Melon\Util;

class Melon {
	
	static protected $_melon;

	final protected function __construct() {
		;
	}
	
	static public function init() {
		if( ! self::$_melon ) {
			require __DIR__ . DIRECTORY_SEPARATOR . 'Melon' . DIRECTORY_SEPARATOR . 'Base' . 
				DIRECTORY_SEPARATOR . 'Core.php';
			self::$_melon = new Base\Core();
			self::$_melon->init();
		}
	}
		
	/**
	 * 获取框架环境信息
	 * 
	 * 你可以使用 . 号分隔的形式获取多维数组里的值：
	 * Melon::env( 'config.charset' );
	 * 
	 * @param string $var [可选] 指定获取哪个值，如果不填此项，则返回所有
	 * @return mixed
	 */
	final static public function env( $var = null ) {
		if( is_null( $var ) ) {
			return self::$_melon->env;
		}
		return Base\Func\getValue( self::$_melon->env, $var );
	}
	
	/**
	 * 调试信息
	 * 
	 * 对{@link \Melon::logMessage}的封装
	 * 它根据程序配置，可以输出到浏览器，也可以写入日志文件
	 * 
	 * @param mixed $message 调试信息
	 * @param mixed $_ 可继续添加调试信息
	 * @return void
	 */
	final static public function debug( $message, $_ = null ) {
		$trace = debug_backtrace();
		$firstTrace = array_shift( $trace );
		foreach( func_get_args() as $message ) {
			self::$_melon->log( 'Debug', $message, $firstTrace['file'], $firstTrace['line'] );
		}
	}
	
	/**
	 * 调试信息，显示方法栈
	 * 
	 * 对{@link \Melon::logMessage}的封装
	 * 它根据程序配置，可以输出到浏览器，也可以写入日志文件
	 * 
	 * @param mixed $message 调试信息
	 * @param mixed $_ 可继续添加调试信息
	 * @return void
	 */
	final static public function debugWithTrace( $message, $_ = null ) {
		$trace = debug_backtrace();
		$firstTrace = array_shift( $trace );
		$file = $firstTrace['file'];
		$line = $firstTrace['line'];
		foreach( func_get_args() as $message ) {
			self::$_melon->log( 'Debug', $message, $firstTrace['file'], $firstTrace['line'], $trace );
		}
	}
	
	/**
	 * 日志助手
	 * 
	 * @param string $dir 日志存放目录
	 * @param string [可选] $filePrefix 日志前缀
	 * @param string [可选] $splitSize 自动分割大小，单位M，当为0时不进行分割
	 * @throws \Melon\Exception\RuntimeException
	 */
	final static public function logger( $dir, $filePrefix = 'log', $splitSize = 10 ) {
		$dir = Base\PathTrace::real( $dir ) ?: $dir;
		return new Base\Logger( $dir, $filePrefix, $splitSize );
	}
	
	final static public function thowException( $message, $code, $previous ) {
		throw new Exception\RuntimeException( $message, $code, $previous );
	}


	/*************************************
	 * 加载
	 *************************************/
	
	/**
	 * 载入一个脚本
	 * 
	 * 它可以像require_once一样防止重复载入同一个脚本
	 * 
	 * @param string $script 脚本路径，你可以使用相对路径，程序会自动将其转为绝对路径
	 * @return void
	 * @throws Exception\RuntimeException
	 */
	final static public function load( $script ) {
		$load = Base\PathTrace::real( $script, true );
		if( ! $load ) {
			throw new Exception\RuntimeException( "无法识别{$script}脚本文件" );
		}
		self::$_melon->load( $load['source'], $load['target'] );
	}
	
	/**
	 * 获取载入脚本文件时返回的数据
	 * 
	 * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
	 * 它不会像{@link Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static public function acquire( $script ) {
		$load = Base\PathTrace::real( $script, true );
		if( ! $load ) {
			trigger_error( "无法识别{$script}脚本", E_USER_WARNING );
			return false;
		}
		return self::$_melon->acquire( $load['source'], $load['target'] );
	}
	
	
	/*************************************
	 * 包加载
	 *************************************/
	
	/**
	 * 从包中载入一个脚本
	 * 
	 * 和{@link Melon::load}一样，它也会防止重复载入同一个脚本
	 * 
	 * @param string $script 脚本路径，必需是相对于包的路径
	 * @return void
	 * @throws Exception\RuntimeException
	 */
	final static public function packageLoad( $script ) {
		$source = Base\PathTrace::source();
		$packageDir = self::$_melon->packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		self::$_melon->load( $source, $target );
	}
	
	/**
	 * 从包中获取载入脚本文件时返回的数据
	 * 
	 * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
	 * 它不会像{@link Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径，必需是相对于包的路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static public function packageAcquire( $script ) {
		$source = Base\PathTrace::source();
		$packageDir = self::$_melon->packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		return self::$_melon->acquire( $source, $target );
	}
	
	/**
	 * 获取当前脚本所在的包的路径
	 * 
	 * @return string 包的路径
	 */
	final static public function packageDir() {
		return self::$_melon->packageDir( Base\PathTrace::source() );
	}
	
	final static public function httpRoute( $config = array() ) {
		return new Http\Route( $config );
	}
	
	final static public function httpRequest() {
		return Http\Request::getInstance();
	}
	
	final static public function httpResponse( $httpVersion = '1.1', $charset = '', $contentType = 'text/html' ) {
		if( ! $charset ) {
			$charset = self::env( 'config.charset' );
		}
		return new Http\Response( $httpVersion, $charset, $contentType );
	}
	
	final static public function httpSimpleRest( $route = null, $request = null,
			$response = null, $matchMode = Http\SimpleRest::MATCH_ONE ) {
		if( is_null( $route ) ) {
			$route = self::httpRoute();
		}
		if( is_null( $request ) ) {
			$request = self::httpRequest();
		}
		if( is_null( $response ) ) {
			$response = self::httpResponse();
		}
		return new Http\SimpleRest( $route, $request, $response, $matchMode );
	}
	
	final static public function set( $items = array(), $replaceMode = Util\Set::REPLACE_ABSOLUTE ) {
		return new Util\Set( $items, $replaceMode );
	}
	
	final static public function template( $tag = array( '{', '}' ) ) {
		return new Util\Template( $tag );
	}
	
	final static public function callable( $name ) {
		return array( array(
			'name' => $name,
			'pic' => '1'
		),  array(
			'name' => 'test.txt',
			'pic' => '1'
		));
	}
}

class M extends Melon {}

M::init();
$s = microtime(true);
$template = new Util\Template();
$template->setCompileDir( './Melon/Data/' )->setTemplateDir('./Melon/Data/')->assign('arr', array(1, 2, 3))->assignTag('list', array(
	'callable' => '\Melon::callable',
	'args' => array(
		'name' => 'haha'
	)
))->display('subTemplate.html');

echo number_format(microtime(true) - $s, 4);

//todo::支持自定义错误页面
//todo::将私有尽量设置为可继承

$rest = M::httpSimpleRest();
$rest->get('/', function() {
	$request = M::httpRequest();
	M::debug($request->inputs());
});

$rest->get('/[id]/[book]/[dd]', function($id, $book) {
	M::httpResponse()->send($book);
});

$rest->get('/[id]/[book:\w+]', function($id, $book) {
	M::httpResponse()->send($book);
});

if(!$rest->matchTotal()) {
	echo '你要的页面找不到了！' . $a;
}
