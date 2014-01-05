<?php

namespace Melon\Base;

defined('IN_MELON') or die('Permission denied');

use Melon\Base;
use Melon\Exception;
use Melon\Http;
use Melon\Util;

function_exists( 'set_magic_quotes_runtime' ) and @set_magic_quotes_runtime(0);

// 异常错误，和E_系列的常量一起被用于框架的错误处理
// 当然它不能用于error_reporting之类的原生错误处理函数
// 65534是根据E_常量的定义规则，由E_ALL x 2得出
defined( 'E_EXCEPTION' ) or define( 'E_EXCEPTION', 65534 );

define( 'MELON_DEBUG', 'Debug' );

/**
 * Melon的扣肉
 */
class Core {
	
	public $env = array();
	
	public $conf = array();
	
	public $loaderSet;
	
	public $loaderPermission;
	
	public $logger;
	
	protected $_init = false;
	
	public function __construct() {
		;
	}
	
	public function init( $root = null, $config = array() ) {
		if( $this->_init ) {
			return;
		}
		$this->_initConf( $root, $config );
		$this->_initLoader();
		$this->_initPhpRigster();
		$this->_initLogger();
		
		// 一切就绪后屏蔽错误
		error_reporting( 0 );
		$this->_init = true;
	}
	
	protected function _initConf( $root, $config ) {
		if( $root && ! is_dir( $root ) ) {
			exit( 'root目录无效' );
		}
		// 客户端连接类型
		$clientType = 'other';
		if( ( isset( $_SERVER["HTTP_X_REQUESTED_WITH"] ) &&
			strtolower( $_SERVER["HTTP_X_REQUESTED_WITH"] ) === 'xmlhttprequest' ) ||
			( isset( $_REQUEST['inajax'] ) && $_REQUEST['inajax'] == 1 ) ) {
			$clientType = 'ajax';
		} elseif( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$clientType = 'browser';
		} elseif( stripos( PHP_SAPI, 'CGI' ) === 0 ) {
			$clientType = 'cgi';
		}
		// 环境变量
		$melonRoot = realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' );
		$this->env = array(
			'root' => $root ?: $melonRoot,
			'melonRoot' => $melonRoot,
			'melonLibrary' =>  $melonRoot . DIRECTORY_SEPARATOR . 'Melon',
			'clientType' => $clientType
		);
		
		// 载入基础配置
		$this->conf = require ( $this->env['melonLibrary'] . DIRECTORY_SEPARATOR .
				'Data' . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Base.php' );
		$this->env['config'] = &$this->conf;
		$this->conf = array_merge( $this->conf, $config );
		// includePath是loader － 包括autoload、权限审查等函数的工作范围
		// 需要把MELON的基础目录添加到includePath中
		$this->_addIncludePath( $this->env['root'] );
		$this->_addIncludePath( $this->env['melonRoot'] );
		// 设置编码
		if( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=' . $this->conf['charset'] );
		}
		
		// 设置时间
		if( ! empty( $this->conf['timezone'] ) ) {
			date_default_timezone_set( $this->conf['timezone'] );
		}
		$microtime = microtime( true );
		$this->env['time'] = intval( $microtime );
		$this->env['microtime'] = $microtime;
	}
	
	protected function _addIncludePath( $path ) {
		if( ! in_array( $path, $this->conf['includePath'] ) ) {
			$this->conf['includePath'][] = $path;
		}
	}


	protected function _initLoader() {
		$melonLibrary = $this->env['melonLibrary'] . DIRECTORY_SEPARATOR;
		// 现在准备一些必需的类
		$autoload = array(
			$melonLibrary . 'Util' . DIRECTORY_SEPARATOR . 'Set.php',
			$melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'Func.php',
			$melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'LoaderSet.php',
			$melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'PathTrace.php',
			$melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'LoaderPermission.php',
		);
		// 用一个数组来保存上面的类的信息
		// 因为等下我要告诉loader，它们已经被载入过了，不要重复载入
		$scripts = array();
		foreach( $autoload as $script ) {
			require_once $script;
			$scripts[ $script ] = $script;
		}
		// 我需要一个保存已载入的脚本文件信息的对象
		// 这样可以不需要使用include_once或者require_once，也可以达到它们那样的效果
		// 把刚才已加载的类的信息添加进去
		$this->loaderSet = new Base\LoaderSet( $scripts,
			Base\LoaderSet::REPLACE_NOT );
		// 载入文件时还需要一个权限审查对象
		$this->loaderPermission = new Base\LoaderPermission(
			$this->conf['includePath'], $this->conf['privatePre']
		);
	}
	
	protected function _initPhpRigster() {
		$core = $this;
		
		spl_autoload_register( array( $core, 'autoLoad' ) );
		
		set_exception_handler( function( $exception ) use( $core ) {
			$core->log( E_EXCEPTION, $exception->getMessage(), $exception->getFile(),
				$exception->getLine(), $exception->getTrace() );
		} );
		
		set_error_handler( function( $type, $message, $file, $line ) use( $core ) {
			$trace = debug_backtrace();
			array_shift( $trace );
			$core->log( $type, $message, $file, $line, $trace );
		} );
		
		register_shutdown_function( function() use( $core ) {
			$error = error_get_last();
			$logTypes = array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR );
			if( ! empty( $error ) && in_array( $error['type'], $logTypes ) ) {
				$core->log( $error['type'], $error['message'], $error['file'], $error['line'] );
			}
		} );
	}
	
	protected function _initLogger() {
		$this->logger = new Base\Logger( $this->env['root'] . DIRECTORY_SEPARATOR .
			$this->conf['logDir'], 'runtime', $this->conf['logSplitSize'] );
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
	public function autoLoad( $class ) {
		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		foreach( $this->conf['includePath'] as $path ) {
			$script = realpath( $path . DIRECTORY_SEPARATOR . $file );
			if( $script ) {
				$this->load( Base\PathTrace::source(), $script );
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
	public function load( $source, $target ) {
		$loaded = $this->loaderSet->has( $target );
		if( ! $loaded && ! is_file( $target ) ) {
			throw new Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! $this->loaderPermission->verify( $source, $target ) ) {
			throw new Exception\RuntimeException( "{$source}脚本文件没有权限载入{$target}" );
		}
		if( ! $loaded ) {
			require $target;
			$this->loaderSet->set( $target, $target );
		}
	}
	
	/**
	 * acquire逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	public function acquire( $source, $target ) {
		if( ! is_file( $target ) ) {
			trigger_error( "{$target}不是一个文件，不能载入它", E_USER_ERROR );
		}
		if( ! $this->loaderPermission->verify( $source, $target ) ) {
			trigger_error( "{$source}脚本文件没有权限载入{$target}", E_USER_ERROR );
		}
		return ( include $target );
	}
	
	
	/**
	 * packageDir逻辑的主要实现
	 * 
	 * @param string $source 载入源路径
	 * @return string 包的路径
	 */
	public function packageDir( $source ) {
		$sourceDir = dirname( $source );
		$parentPos = strrpos( $sourceDir, DIRECTORY_SEPARATOR . $this->conf['privatePre'] );
		if( $parentPos ) {
			$spos = ( $parentPos + strlen( DIRECTORY_SEPARATOR ) );
			$epos = strpos( $sourceDir, DIRECTORY_SEPARATOR, $spos );
			if( $epos ) {
				return substr( $sourceDir, 0, $epos );
			}
		}
		trigger_error( '当前脚本不存在于任何包中', E_USER_ERROR );
		return null;
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
	public function log( $type, $message, $file = null, $line = null, $trace = null ) {
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
		// 显示日志信息
		$showCodeSnippet = !! $this->conf['htmlShowCodeSnippet'];
		$logHandler( $this->conf['logDisplayLevel'], function( $debugMessage ) use ( $showCodeSnippet ) {
			$debugMessage->show( Base\DebugMessage::DISPLAY_AUTO, true, $showCodeSnippet );
		} );
		
		// 写入日志信息
		if( isset( $this->logger ) ) {
			$logger = $this->logger;
			$logHandler( $this->conf['logLevel'], function( $debugMessage ) use ( $logger, $type ) {
				$showTrace = in_array( $type, array( E_ERROR, E_PARSE,  E_COMPILE_ERROR,
					E_CORE_ERROR, E_EXCEPTION, MELON_DEBUG ) );
				$text = $debugMessage->parse( Base\DebugMessage::DISPLAY_TEXT, $showTrace );
				$logger->write( $text );
			} );
		}
		
		// 显示出错信息
		if( in_array( $type, array( E_ERROR, E_PARSE,  E_COMPILE_ERROR, E_CORE_ERROR,
			E_EXCEPTION ) ) ) {
			$errorPage = $this->env['root'] . DIRECTORY_SEPARATOR . $this->conf['errorPage'];
			$errorMessage = $this->conf['errorMessage'];
			if( $this->env['clientType'] === 'browser' && file_exists( $errorPage ) ) {
				ob_start();
				@include $errorPage;
				$errorMessage = ob_get_contents();
				ob_clean();
			}
			\Melon::httpResponse()->send( $errorMessage ?: 'Server error.', 500 );
		}
	}
}