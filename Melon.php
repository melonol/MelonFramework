<?php

define( 'IN_MELON', true );

use Melon\Base;
use Melon\Cache;
use Melon\Exception;
use Melon\File;
use Melon\Util;
use Melon\Database;

if( function_exists('set_magic_quotes_runtime') ) {
	set_magic_quotes_runtime(0);
}

// 客户端连类型
if( ! defined( 'CLIENT_TYPE' ) ) {
	if( ( isset( $_SERVER["HTTP_X_REQUESTED_WITH"] ) &&
		strtolower( $_SERVER["HTTP_X_REQUESTED_WITH"] ) === 'xmlhttprequest' ) ||
		( isset( $_REQUEST['inajax'] ) && $_REQUEST['inajax'] == 1 ) ) {
		define( 'CLIENT_TYPE', 'AJAX' );
	} elseif( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		define( 'CLIENT_TYPE', 'BROWSER' );
	} elseif( stripos( PHP_SAPI, 'CGI' ) === 0 ) {
		define( 'CLIENT_TYPE', 'CGI' );
	} else {
		define( 'CLIENT_TYPE', 'OTHER' );
	}
}

// 异常错误，和E_系列的常量一起被用于框架的错误处理
// 当然它不能用于error_reporting之类的原生错误处理函数
// 65534是根据E_常量的定义规则，由E_ALL x 2得出
define( 'E_EXCEPTION', 65534 );


class Melon {
	
	static private $_melon;

	final protected function __construct() {
		;
	}
	
	static public function init() {
		if( defined( 'MELON_INIT' ) ) {
			return;
		}
		error_reporting( 0 );
		
		// 注册autoload
		// 不过现在它还不能用，一些必要的数据还没初始化
		// 放在前面是为了让你能看到它的存在 :)
		spl_autoload_register( '\Melon::autoload' );
		
		set_exception_handler( function( $exception ) {
			Melon::log( E_EXCEPTION, $exception->getMessage(), $exception->getFile(),
				$exception->getLine(), $exception->getTrace() );
		} );
		
		set_error_handler( function( $type, $message, $file, $line ) {
			$trace = debug_backtrace();
			array_shift( $trace );
			Melon::log( $type, $message, $file, $line, $trace );
		} );
		
		register_shutdown_function( function() {
			$error = error_get_last();
			$logTypes = array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR );
			if( ! empty( $error ) && in_array( $error['type'], $logTypes ) ) {
				Melon::log( $error['type'], $error['message'], $error['file'], $error['line'] );
			}
		} );
		
		// 我把属性都放到$_melon变量中，因为Melon很可能会被扩展（继承）
		// 为了方便，我都是用了self来读取属性和方法
		// 因为如果属性太多，到时和子类的属性冲突的机率就越大
		// 用单属性的话，到时只需要管住它就可以了
		$melon = self::$_melon = new \stdClass();
		
		// env负责保存一些系统基本的信息
		$melon->env = array(
			'root' => __DIR__,
			'library' =>  __DIR__  . DIRECTORY_SEPARATOR . 'Melon',
		);
		
		// 载入基础配置
		$melon->conf = require ( $melon->env['library'] . DIRECTORY_SEPARATOR .
				'Data' . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Base.php' );
		// includePath是loader － 包括autoload、权限审查等函数的工作范围
		// 需要把MELON的基础目录添加到includePath中
		$melon->conf['includePath'][] = $melon->env['root'];
		$melon->env['config'] = &$melon->conf;
		
		// 设置编码
		header( 'Content-Type: text/html; charset=' . $melon->conf['charset'] );
		
		// 设置时间
		if( ! empty( $melon->conf['timezone'] ) ) {
			date_default_timezone_set( $melon->conf['timezone'] );
		}
		$microtime = microtime( true );
		$melon->env['time'] = intval( $microtime );
		$melon->env['microtime'] = $microtime;
		
		// 初始化loader
		self::_initLoader();
		$melon->logger = new Base\Logger( $melon->env['library'] . DIRECTORY_SEPARATOR .
			'Data' . DIRECTORY_SEPARATOR . 'Log', 'runtime', $melon->conf['logSplitSize'] );
		
		//TODO::重构
		require $melon->env['library'] . DIRECTORY_SEPARATOR . 'Base' . DIRECTORY_SEPARATOR . 'Func.php';
		define( 'MELON_INIT', true );
	}
	
	/**
	 * 初始化loader
	 * 它是一些加载脚本或者文件的必需前提条件
	 * 
	 * @return void
	 */
	static private function _initLoader() {
		$library = self::$_melon->env['library'] . DIRECTORY_SEPARATOR;
		// 现在准备一些必需的类
		$autoload = array(
			$library . 'Util' . DIRECTORY_SEPARATOR . 'Set.php',
			$library . 'File' . DIRECTORY_SEPARATOR . 'LoaderSet.php',
			$library . 'File' . DIRECTORY_SEPARATOR . 'PathTrace.php',
			$library . 'File' . DIRECTORY_SEPARATOR . 'LoaderPermission.php',
		);
		// 用一个数组来保存上面的类的信息
		// 因为等下我要告诉loader，它们已经被载入过了，不要重复载入
		$scripts = array();
		// MELON_TEST是我做单元测试的时候创建的
		// 直接整合进来有点不太好，不过这是最简单的方式
		if( defined( 'MELON_TEST' ) ) {
			foreach( $autoload as $script ) {
				require_once $script;
				$scripts[ $script ] = $script;
			}
		} else {
			foreach( $autoload as $script ) {
				require $script;
				$scripts[ $script ] = $script;
			}
		}
		
		// 我需要一个保存已载入的脚本文件信息的对象
		// 这样可以不需要使用include_once或者require_once，也可以达到它们那样的效果
		// 把刚才已加载的类的信息添加进去
		self::$_melon->loaderSet = new File\LoaderSet( $scripts,
			File\LoaderSet::REPLACE_NOT );
		// 载入文件时还需要一个权限审查对象
		self::$_melon->loaderPermission = new File\LoaderPermission(
			self::$_melon->conf['includePath'], self::$_melon->conf['privatePre']
		);
	}

	/**
	 * 记录日志
	 * 
	 * 主要是错误方面，当然也可以是调试信息
	 * 它根据程序配置，可以输出到浏览器，也可以写入日志文件
	 * 
	 * @param string $type 消息类型，目前用来显示给用户看的类型，以后可能还有其它作用
	 * @param mixed $message 消息，它是一个整个事件的主要描述
	 * @param string $file [可选] 脚本，消息所描述的事件发生在哪个脚本
	 * @param int $line [可选] 所在的行，消息所描述的事件发生在脚本中的那一行
	 * @param array $trace [可选] 调用方法栈，这个是使用debug_backtrace方法、捕获异常等方式得到的栈
	 */
	final static public function log( $type, $message, $file = null, $line = null, $trace = null ) {
		static $typeMap = array(
			E_COMPILE_ERROR => 'Compile error',
			E_COMPILE_WARNING => 'Compile warning',
			E_CORE_ERROR => 'Core error',
			E_CORE_WARNING => 'Core warning',
			E_DEPRECATED => 'Deprecated',
			E_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_NOTICE => 'Notice',
			E_PARSE => 'Parsing error',
			E_RECOVERABLE_ERROR => 'Recoverable error',
			E_STRICT => 'Strict',
			E_USER_DEPRECATED => 'User deprecated',
			E_USER_ERROR => 'User error',
			E_USER_WARNING => 'User warning',
			E_USER_NOTICE => 'User notice',
			E_EXCEPTION => 'Exception',
		);
		
		/**
		 * 日志助手
		 * 
		 * @param int $level 等级，等级决定是否执行回调函数
		 *  0不执行回调；1异常和致命错误执行回调；2所有错误类型都执行回调；3所有类型都执行回调
		 * @param Closure $callback 回调函数
		 * @return void
		 * @TODO 能解释xdebug的trace、可配置是否显示源代码
		 */
		$logHandler = function( $level, $callback ) use( &$typeMap, $type, $message, $file, $line, $trace ) {
			// 处理错误消息的实例
			// 我知道当前函数会被调用多次，所以做一个静态变量保存
			// 这样可以省下一次实例，我吝啬这一点
			// 实际运行中，如果不注意，NOTICE和WARNING可能会很多
			static $debugMessage = null;
			if( $level === 0 ) {
				return;
			} else if( $level === 1 && ! in_array( $type, array( E_ERROR, E_PARSE,
				E_COMPILE_ERROR, E_CORE_ERROR, E_EXCEPTION ) )  ) {
				return;
			} else if( $level === 2 && ! in_array( $type, array_keys( $typeMap ) ) ) {
				return;
			} else {
				if( is_null( $debugMessage ) ) {
					$_type = ( isset( $typeMap[ $type ] ) ? $typeMap[ $type ] : $type );
					$debugMessage = new Base\DebugMessage( $_type, $message, $file, $line, $trace );
				}
				$callback( $debugMessage );
			}
		};
		// 显示
		$logHandler( self::$_melon->conf['logDisplayLevel'], function( $debugMessage ) {
			$debugMessage->show();
		} );
		// 写入
		if( isset( self::$_melon->logger ) ) {
			$logger = self::$_melon->logger;
			$logHandler( self::$_melon->conf['logLevel'], function( $debugMessage ) use ( $logger ) {
				$text = $debugMessage->parse( Base\DebugMessage::DISPLAY_TEXT );
				$logger->write( $text );
			} );
		}
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
	final static public function debug( $message, $_ = null) {
		$trace = debug_backtrace();
		$firstTrace = array_shift( $trace );
		foreach( func_get_args() as $message ) {
			self::log( 'Debug', $message, $firstTrace['file'], $firstTrace['line'] );
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
	final static public function debugWithTrace( $message, $_ = null) {
		$trace = debug_backtrace();
		$firstTrace = array_shift( $trace );
		$file = $firstTrace['file'];
		$line = $firstTrace['line'];
		foreach( func_get_args() as $message ) {
			self::log( 'Debug', $message, $firstTrace['file'], $firstTrace['line'], $trace );
		}
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
	 * @throws Exception\RuntimeException
	 */
	final static public function load( $script ) {
		$load = File\PathTrace::repair( $script, true );
		if( ! $load ) {
			throw new Exception\RuntimeException( "无法识别{$script}脚本文件" );
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
	 */
	final static public function autoLoad( $class ) {
		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		foreach( self::$_melon->conf['includePath'] as $path ) {
			$script = realpath( $path . DIRECTORY_SEPARATOR . $file );
			if( $script ) {
				self::_load( File\PathTrace::sourceFile(), $script );
			}
		}
	}
	
	/**
	 * load和autoLoad逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标脚本路径
	 * @return void
	 * @see \Melon::load
	 * @see \Melon::autoLoad
	 * @throws Exception\RuntimeException
	 */
	final static private function _load( $source, $target ) {
		$loaded = self::$_melon->loaderSet->has( $target );
		if( ! $loaded && ! is_file( $target ) ) {
			throw new Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			throw new Exception\RuntimeException( "{$source}脚本文件没有权限载入{$target}" );
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
	 * 它不会像{@link Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static public function acquire( $script ) {
		$load = File\PathTrace::repair( $script, true );
		if( ! $load ) {
			// TODO::改为抛出警告
			throw new Exception\RuntimeException( "无法识别{$script}脚本" );
		}
		return self::_acquire( $load['source'], $load['target'] );
	}
	
	/**
	 * acquire逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static private function _acquire( $source, $target ) {
		if( ! is_file( $target ) ) {
			trigger_error( "{$target}不是一个文件，不能载入它", E_USER_ERROR );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			trigger_error( "{$source}脚本文件没有权限载入{$target}", E_USER_ERROR );
		}
		return ( include $target );
	}
	
	final static public function file() {
	}
	
	/******************************************************************
	 * 包加载
	 ******************************************************************/
	
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
		$source = File\PathTrace::sourceFile();
		$packageDir = self::_packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		self::_load( $source, $target );
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
		$source = File\PathTrace::sourceFile();
		$packageDir = self::_packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		return self::_acquire( $source, $target );
	}
	
	/**
	 * 获取当前脚本所在的包的路径
	 * 
	 * @return string 包的路径
	 */
	final static public function packageDir() {
		return self::_packageDir( File\PathTrace::sourceFile() );
	}
	
	/**
	 * packageDir逻辑的主要实现
	 * 
	 * @param string $source 载入源路径
	 * @return string 包的路径
	 */
	final static private function _packageDir( $source ) {
		$sourceDir = dirname( $source );
		$parentPos = strrpos( $sourceDir, DIRECTORY_SEPARATOR . self::$_melon->conf['privatePre'] );
		if( $parentPos ) {
			$spos = ( $parentPos + strlen( DIRECTORY_SEPARATOR ) );
			$epos = strpos( $sourceDir, DIRECTORY_SEPARATOR, $spos );
			if( $epos ) {
				return substr( $sourceDir, 0, $epos );
			}
		}
		// TODO::抛出警告
		return null;
	}
	
	/**
	 * 获取框架的一些基本信息
	 * 
	 * @param string $var [可选] 指定获取哪个值，如果不填此项，则返回所有
	 * @return mixed
	 */
	static public function env( $var = null ) {
		return is_null( $var ) ? self::$_melon->env : 
			( isset( self::$_melon->env[ $var ] ) ? self::$_melon->env[ $var ] : null );
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	final static public function run() {
		
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
$template = new Base\Template();
$template->setCompileDir( './Melon/Data/' )->setTemplateDir('./Melon/Data/')->assign('arr', array(1, 2, 3))->assignTag('list', array(
	'callable' => '\Melon::callable',
	'args' => array(
		'name' => 'haha'
	)
))->display('subTemplate.html');

echo number_format(microtime(true) - $s, 4);

//todo::env支持以.的方式获取
//todo::支持自定义错误页面