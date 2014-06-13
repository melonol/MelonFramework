<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Base;

defined('IN_MELON') or die('Permission denied');

use Melon\Base;
use Melon\Exception;
use Melon\Http;
use Melon\Util;
use Melon\Database;
use Melon\Database\PDO;

function_exists( 'set_magic_quotes_runtime' ) and @set_magic_quotes_runtime(0);

// 异常错误，和E_系列的常量一起被用于框架的错误处理
// 当然它不能用于error_reporting之类的原生错误处理函数
// 65534是根据E_常量的定义规则，由E_ALL x 2得出
defined( 'E_EXCEPTION' ) or define( 'E_EXCEPTION', 65534 );

// 调试标记
// 使用相关log方法时作为TYPE的类型参数，而并不是调试开关
define( 'MELON_DEBUG', 'Debug' );

/**
 * Melon的扣肉，提供基本的载入脚本、错误处理、日志等功能
 * 
 * 扣肉有自己的env环境变量，保存基本的运行数据
 * 以includePath为autoLoad、权限管理的工作目录，不在includePath里的文件将无效
 * 你可以在配置中增加这些目录，程序默认把root（框架的root非系统）添加到includePath中
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 */
class Core {
    
    /**
     * 环境变量
     * 
     * @var array 
     */
   public $env = array(
        'version' => '0.2.3'
    );
    
    /**
     * 框架配置
     * 
     * @var array 
     */
    public $conf = array();
    
    /**
     * 保存已载入脚本的容器
     * 
     * @var \Melon\Base\LoaderSet 
     */
    public $loaderSet;
    
    /**
     * 载入脚本的权限管理器
     * 
     * @var \Melon\Base\LoaderPermission
     */
    public $loaderPermission;
    
    /**
     * 日志助手
     * 
     * @var \Melon\Base\Logger
     */
    public $logger;
    
    /**
     * 数据库驱动实例
     * 
     * @var \object 
     */
    public $dbDriver = null;
    
    /**
     * 应用运行实例
     * 
     * @var \Melon\Base\App
     */
    protected $_app = null;
    
    /**
     * 初始化标记
     * 
     * @var boolean 
     */
    protected $_inited = false;
    

    public function __construct() {
        ;
    }
    
    /**
     * 初始化核心，只能被初始化一次，重复将忽略
     * 
     * @param array $config 应用配置
     * @param array $baseConfig 0.1版的配置项，废器使用，已集成到$config[baseConfig]中
     * @return void
     */
    public function init( $config = null, $baseConfig = array() ) {
        if( $this->_inited ) {
            return;
        }
        $this->_initConf( $config, $baseConfig );
        $this->_initLoader();
        $this->_initPhpRigster();
        $this->_initLogger();
        // app
        if( $this->env['runType'] === 'app' ) {
            $this->_initApp( $config );
        }
        // 数据库配置
        $this->_initDB();
        $this->_inited = true;
    }
    
    /**
     * 初始化一些配置信息
     * 
     * @param array $config 应用配置
     * @param array $baseConfig 0.1版的配置项，废器使用，已集成到$config[config]中
     * @return void
     */
    protected function _initConf( $config, $baseConfig ) {
        // 兼容0.1版本的初始化模式
        if( ! is_array( $config ) ) {
            $root = $config;
            $baseConfig = is_array( $baseConfig ) ? $baseConfig : array();
            $config = array(
                'type' => 'normal',
                'root' => $root,
                'config' => $baseConfig,
            );
        }
        $runType = ( isset( $config['type'] ) && in_array( $config['type'], array( 'normal', 'app' ) ) ?
                $config['type'] : 'normal' );
        $rootPath = ( isset( $config['root'] ) ? realpath( $config['root'] ) : null );
        if( isset( $config['root'] ) && ! $rootPath ) {
            exit( '应用目录无效' );
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
        
        $melonRoot = realpath( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' );
        $this->env = array_merge( $this->env, array(
            'runType' => $runType,
            'root' => $rootPath ?: $melonRoot,
            'melonRoot' => $melonRoot,
            'melonLibrary' =>  $melonRoot . DIRECTORY_SEPARATOR . 'Melon',
            'clientType' => $clientType,
            'install' => ( isset( $config['install'] ) ? $config['install'] : false ),
        ) );
        
        // 载入基础配置
        $this->conf = require ( $this->env['melonLibrary'] . DIRECTORY_SEPARATOR .
                'Data' . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Base.php' );
        $this->env['config'] = &$this->conf;
        if( isset( $config['config'] ) && is_array( $config['config'] ) ) {
            $this->conf = array_replace_recursive( $this->conf, $config['config'] );
        }
        
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
    
    /**
     * 添加一个inlucdePath
     * 
     * 简单的使用in_array排重，不使用realpath格式化，不能做到完全排重
     * 但已经足够了
     * 
     * @param string $path
     * @return void
     * @todo 使用realpath格式化
     */
    protected function _addIncludePath( $path ) {
        if( ! in_array( $path, $this->conf['includePath'] ) ) {
            $this->conf['includePath'][] = $path;
        }
    }

    /**
     * 初始化加载器
     * 
     * @return void
     */
    protected function _initLoader() {
        $melonLibrary = $this->env['melonLibrary'] . DIRECTORY_SEPARATOR;
        // 现在准备一些必需的类
        $autoload = array(
            $melonLibrary . 'Util' . DIRECTORY_SEPARATOR . 'Set.php',
            $melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'Func.php',
            $melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'Logger.php',
            $melonLibrary . 'Base' . DIRECTORY_SEPARATOR . 'DebugMessage.php',
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
    
    /**
     * 添加php注册器－autoload、异常和错误处理事件注册
     * 
     * @return void
     */
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
    
    /**
     * 初始化日志助手
     * 
     * @return void
     */
    protected function _initLogger() {
        $_logDir = $this->env['melonLibrary'] . DIRECTORY_SEPARATOR . $this->conf['logDir'];
        $logDir = Func\isAbsolutePath( $this->conf['logDir'] ) ? $this->conf['logDir'] : $_logDir;
        $this->logger = new Base\Logger( $logDir, 'runtime', $this->conf['logSplitSize'] );
    }
    
    /**
     * 数据库初始化
     * 
     * @throws Exception\RuntimeException
     */
    protected function _initDB() {
        $dbEnv = array();
        $this->env['db'] =& $dbEnv;
        $dbConfig = ( isset( $this->conf['database'] ) ? $this->conf['database'] : array() );
        $tablePrefix = ( isset( $dbConfig['tablePrefix'] ) ? strval( $dbConfig['tablePrefix'] ) : '' );
        $dbEnv['tablePrefix'] = $tablePrefix;
        if( isset( $dbConfig['driver'] ) ) {
            if(  ! is_object( $dbConfig['driver'] ) && ! isset( $dbConfig['driver']['dsn'] ) ) {
                throw new Exception\RuntimeException('请提供有效的数据库驱动信息');
            } else {
                if( is_object( $dbConfig['driver'] ) ) {
                    $this->dbDriver = $dbConfig['driver'];
                } elseif( isset( $dbConfig['driver']['dsn'] ) && $dbConfig['driver']['dsn'] ) {
                    $dsn = $dbConfig['driver']['dsn'];
                    $username = ( isset( $dbConfig['driver']['username'] ) ? $dbConfig['driver']['username'] : null );
                    $password = ( isset( $dbConfig['driver']['password'] ) ? $dbConfig['driver']['password'] : null );
                    $options = ( isset( $dbConfig['driver']['options'] ) && is_array( $dbConfig['driver']['options'] ) ?
                            $dbConfig['driver']['options'] : array() );
                    try {
                        // TODO::做懒连接
                        $this->dbDriver = new PDO\PDO( $dsn, $username, $password, $options );
                    } catch ( \PDOException $e ) {
                        throw new Exception\RuntimeException( '数据库连接失败', null, $e );
                    }
                    unset( $dbConfig['driver'] );
                }
            }
        }
    }

    /**
     * 初始化APP（MVC模式）
     */
    protected function _initApp( $config ) {
        $this->load( __FILE__, __DIR__ . DIRECTORY_SEPARATOR . 'App.php' );
        $this->_app = new App( $this );
        $this->_app->init( $config );
    }
    
    /**
     * 获得APP实例
     */
    public function app() {
        if( ! $this->_app ) {
            throw new Exception\RuntimeException( '初始化APP失败，请确认配置是否正确' );
        }
        return $this->_app;
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
        if(strpos($class, '_Demo')) define('test', 1);
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
     * @return void
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
            if( Func\isAbsolutePath( $this->conf['errorPage'] ) ) {
                $errorPage = $this->conf['errorPage'];
            } else {
                $pageDir = ( $this->env['runType'] === 'app' ?
                    ( isset( $this->env['appDir'] ) ? $this->env['appDir'] : '' ) :
                    $this->env['melonLibrary'] );
                $errorPage = $pageDir . DIRECTORY_SEPARATOR . $this->conf['errorPage'];
            }
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