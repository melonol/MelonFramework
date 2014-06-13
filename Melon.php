<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

define( 'IN_MELON', true );

use Melon\Base;
use Melon\Exception;
use Melon\Http;
use Melon\Util;
use Melon\Database;
use Melon\Database\PDO;

/**
 * 框架的主体类
 * 
 * 主体类是一个纯静态的类，你可以把它作为一个'接口通道'
 * 或者说是快捷方式，提供了框架里基本上所有类的实例操作
 * 当然也有其它实用的方法，比如获取配置、载入脚本、调试等等
 * 所以如果你不熟悉命名空间，也几乎可以不使用命名空间进行开发但还是建议你先对PHP命名空间有基本的认识
 * 
 * 初始化：
 * 使用前请先调用init方法进行初始化
 * 
 * 扩展：
 * Melon提供了一个快捷方式M（其实是Melon的子类）
 * 你可以Melon::env这样调用一个方法，或者M::env
 * 干脆你不想用它们，也可以自己换一个'马甲'，像M一样继承Melon：
 * class Name extends Melon {}
 * Name::init();
 * 然后你就可以在任何地方使用它了
 * 另外继承之后，可以往里添加一些自己的操作方法，非常方便
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
class Melon {
    
    /**
     * 核心类的实例，提供一系列较底层操作
     * 子类也可直接使用，注意不要随意覆盖核心类的属性，可能导致程序不正常运行
     * 
     * @var \Melon\Base\Core
     */
    static protected $_melon;
    
    /**
     * 纯静态类不允许实例化
     */
    final protected function __construct() {
        ;
    }
    
    /**
     * 初始化框架
     * 
     * 初始化操作委托至Core类，具体信息请参考\Melon\Base\Core
     * 该方法一次调用即可，多次调用无效
     * 
     * @param array $config 应用配置
     * @param array $baseConfig 0.1版的配置项，废器使用，已集成到$config[config]中
     * @return void
     */
    static public function init( $config = array(), $baseConfig = array() ) {
        if( ! self::$_melon ) {
            require __DIR__ . DIRECTORY_SEPARATOR . 'Melon' . DIRECTORY_SEPARATOR . 'Base' . 
                DIRECTORY_SEPARATOR . 'Core.php';
            self::$_melon = new Base\Core();
            self::$_melon->init( $config, $baseConfig );
        }
    }
    
    /**
     * 运行APP
     * 
     * APP模式默认是一个MVC，但是这个模式很简单，所以很灵活
     * 你可以扩展为其它模式，例如REST
     * 要使用APP需要在初始化时声明type和其它APP参数，否则会发生异常
     * 
     * @param string $module [可选] module名称，如果你在初始化的时候设置了，这时候就不需要指定此参数
     * @param string $controller [可选] 控制器，如果不提供此参数，程序则调用Route类尝试解释路径
     * @param string $action [可选] 方法，必需先提供控制器，否则该选项无效
     * @param string $args [可选] 参数，必需先提供控制器，否则该选项无效
     * @return void
     */
    static public function runApp( $module = null, $controller = null, $action = null, array $args = array() ) {
        self::$_melon->app()->run( $module, $controller, $action, $args );
    }
    
    /**
     * 终止程序，请使用此代替exit
     * 
     * @param string [可选] $content 内容
     */
    static public function halt( $content = '' ) {
        exit( $content );
    }
    
    /*************************************
     * 环境、调试与异常
     *************************************/
        
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
     * 调试
     * 
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
            self::$_melon->log( MELON_DEBUG, $message, $firstTrace['file'], $firstTrace['line'] );
        }
    }
    
    /**
     * 调试，会显示方法栈
     * 
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
            self::$_melon->log( MELON_DEBUG, $message, $firstTrace['file'], $firstTrace['line'], $trace );
        }
    }
    
    /**
     * 获取一个日志助手实例
     * 
     * @param string $dir 日志存放目录
     * @param string [可选] $filePrefix 日志前缀
     * @param string [可选] $splitSize 自动分割大小，单位M，当为0时不进行分割
     * @return \Melon\Base\Logger
     * @throws \Melon\Exception\RuntimeException
     */
    final static public function logger( $dir, $filePrefix = 'log', $splitSize = 10 ) {
        return new Base\Logger( $dir, $filePrefix, $splitSize );
    }
    
    /**
     * 抛出一个异常
     * 
     * @param string $message 异常消息
     * @param string $code [可选] 异常代码
     * @param \Exception $previous [可选] 异常链中的前一个异常
     * @throws Exception\RuntimeException
     */
    final static public function throwException( $message, $code = null, $previous = null ) {
        throw new Exception\RuntimeException( $message, $code, $previous );
    }


    /*************************************
     * 基础加载
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
        $_script = realpath( $script );
        if( ! $_script ) {
            throw new Exception\RuntimeException( "无法识别{$script}脚本文件" );
        }
        self::$_melon->load( Base\PathTrace::source(), $_script );
    }
    
    /**
     * 获取载入脚本文件时返回的数据
     * 
     * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
     * 它不会像Melon::load那样，可以防止重复载入同一个脚本文件
     * 
     * @param string $script 脚本路径
     * @return mixed
     * @throws Exception\RuntimeException
     */
    final static public function acquire( $script ) {
        $_script = realpath( $script );
        if( ! $_script ) {
            trigger_error( "无法识别{$script}脚本", E_USER_WARNING );
            return false;
        }
        return self::$_melon->acquire( Base\PathTrace::source(), $_script );
    }
    
    
    /*************************************
     * 包加载
     *************************************/
    
    /**
     * 从包中载入一个脚本
     * 
     * 和Melon::load一样，它也会防止重复载入同一个脚本
     * 
     * @param string $script 脚本路径，必需是相对于包的路径
     * @return void
     * @throws Exception\RuntimeException
     */
    final static public function packageLoad( $script ) {
        $source = Base\PathTrace::source();
        $packageDir = self::$_melon->packageDir( $source );
        $target = realpath( $packageDir . DIRECTORY_SEPARATOR . $script );
        if( ! $target ) {
            throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
        }
        self::$_melon->load( $source, $target );
    }
    
    /**
     * 从包中获取载入脚本文件时返回的数据
     * 
     * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
     * 它不会像Melon::load那样，可以防止重复载入同一个脚本文件
     * 
     * @param string $script 脚本路径，必需是相对于包的路径
     * @return mixed
     * @throws Exception\RuntimeException
     */
    final static public function packageAcquire( $script ) {
        $source = Base\PathTrace::source();
        $packageDir = self::$_melon->packageDir( $source );
        $target = realpath( $packageDir . DIRECTORY_SEPARATOR . $script );
        if( ! $target ) {
            trigger_error( "无法在{$packageDir}目录中找到{$script}脚本", E_USER_WARNING );
            return false;
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
    
    
    /*************************************
     * 数据库
     *************************************/
    
    /**
     * 返回框架初始化时提供的数据库驱动实例，如果没有则抛出一个异常
     * 
     * 这是为数据库操作提供一个全局的快捷方式
     * 其实可以当作一个对象容器，只要是对象实例就可以
     * 你可以用自己的数据库驱动，没有具体限制
     * 
     * @return \object
     */
    final static public function db() {
        if( ! is_object( self::$_melon->dbDriver ) ) {
            self::throwException( "数据库驱动实例不存在，不能使用此方法" );
        }
        return self::$_melon->dbDriver;
    }
    
    /**
     * 为一个数据库表名添加上框架初始化时配置的数据库表前缀，并使用`号括起来
     * 
     * 如果没有配置，则前缀为空
     * 
     * @param string $tablename 表名，只能由字母、数字和下划线组成
     * @return string
     */
    final static public function table( $tablename, $safe = true ) {
        if( $safe && ! preg_match( '/^\w+$/', $tablename ) ) {
            self::throwException( "数据库表名{$tablename}不合法" );
        }
        return '`' . self::env( 'db.tablePrefix' ) . $tablename . '`';
    }
    
    /**
     * 获得一个PDO实例
     * 
     * @param string $dsn 包含请求连接到数据库的信息
     * @param string $user [可选] DSN字符串中的用户名
     * @param string $pass [可选] DSN字符串中的密码
     * @param array $driverOptions [可选] 一个具体驱动的连接选项的键=>值数组
     * @return \Melon\Database\PDO\PDO
     */
    final static public function PDO( $dsn, $user = null, $pass = null, array $driverOptions = array() ) {
        return new PDO\PDO( $dsn, $user, $pass, $driverOptions );
    }
    
    /**
     * 获得一个PDOStatement实例
     * 
     * @return \Melon\Database\PDO\Statement
     */
    final static public function PDOStatement() {
        return new PDO\Statement();
    }
    
    /**
     * 获得一个PDO数据模型实例
     * 
     * @param string $table 数据表名，可带数据库前缀
     * @param boolean $table [可选] 是否自动为表添加上框架初始化配置的前缀，默认为ture
     * @param mixed $pdo [可选] PDO实例对象，如果为空并且在框架初始化中提供了PDO的数据库驱动信息，则默认使用它，否则抛出一个异常
     * @return \Melon\Database\PDO\Model
     */
    final static public function PDOModel( $table, $autoPrefix = true, $pdo = null ) {
        $_pdo = $pdo;
        if( is_null( $_pdo ) && is_object( self::$_melon->dbDriver ) ) {
            $_pdo = self::$_melon->dbDriver;
        }
        if( ! $_pdo instanceof \PDO ) {
            self::throwException( '请提供有效的PDO驱动实例' );
        }
        $_table = ( $autoPrefix ? self::table( $table, false ) : $table );
        return new PDO\Model( $_table, $_pdo );
    }
    
    
    /*************************************
     * HTTP库
     *************************************/
    
    /**
     * 获得一个路由实例
     * 
     * 
     * @param array $config 全局路由配置，详情请看self::setConfig方法
     * @param enum $type 路由类型
     * Route::TYPE_AUTO                [默认] 自动识别
     * Route::TYPE_INCOMPLETE          不完全的（带.php）
     * Route::TYPE_COMPLETE            完全的（带.php）
     * Route::TYPE_REQUEST_KEY         通过请求参数指定路由
     * @param string $request 请求参数的名字，当路由类型为Route::TYPE_REQUEST_KEY时，有效
     * @return \Melon\Http\Route
     */
    final static public function httpRoute( $config = array(), $type = Http\Route::TYPE_AUTO, $requestKey = '' ) {
        return new Http\Route( $config, $type, $requestKey );
    }
    
    /**
     * 获得一个用于HTTP请求处理的实例
     * 
     * Request是单例对象，所以不用担心多次调用而增加消耗
     * 
     * @return \Melon\Http\Request
     */
    final static public function httpRequest() {
        return Http\Request::getInstance();
    }
    
    /**
     * 获得一个用于HTTP回应的实例
     * 
     * @param string $httpVersion [可选] 要使用哪个HTTP版本，为空则使用默认值
     * @param string $charset [可选] 回应内容的编码
     * @param string $contentType [可选] 媒体类型
     * @return \Melon\Http\Response
     */
    final static public function httpResponse( $httpVersion = '1.1', $charset = '', $contentType = 'text/html' ) {
        if( ! $charset ) {
            $charset = self::env( 'config.charset' );
        }
        return new Http\Response( $httpVersion, $charset, $contentType );
    }
    
    /**
     * 获取一个Rest实例
     * 
     * @param \Melon\Http\Route $route [可选] 路由
     * @param \Melon\Http\Request $request [可选] HTTP请求处理
     * @param \Melon\Http\Response $response [可选] HTTP回应处理
     * @param enum $matchMode 匹配模式
     * 1. \Melon\Http\SimpleRest::MATCH_ALL 匹配所有符合规则的路由
     * 2. \Melon\Http\SimpleRest::MATCH_ONE 只匹配第一个符合规则的路由，之后都会被忽略
     * @return \Melon\Http\SimpleRest
     */
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
    
    
    /*************************************
     * 其它实用工具
     *************************************/
    
    /**
     * 获取一个Set容器实例
     * 
     * @param array $items [可选] 默认数据
     * @param enum $replaceMode [可选] 替换模式，如果存在相同键名元素时被触发
     * 替换模式分别有：
     * 1. \Melon\Util\Set::REPLACE_NOT            不进行替换
     * 2. \Melon\Util\Set::REPLACE_ABSOLUTE       [默认] 严格，无条件替换原来的值
     * 3. \Melon\Util\Set::REPLACE_RELAXED        宽松，如果$value能够被PHP empty转为假值（null、''、0、false、空数组），则不替换
     * @return \Melon\Util\Set
     */
    final static public function set( $items = array(), $replaceMode = Util\Set::REPLACE_ABSOLUTE ) {
        return new Util\Set( $items, $replaceMode );
    }
    
    /**
     * 获取一个模板视图实例
     * 
     * @param array $tag [可选] 标签名
     * @return \Melon\Util\Template
     */
    final static public function template( array $tag = array() ) {
        return new Util\Template( $tag ?: self::env( 'config.templateTags' ) );
    }
    
    /**
     * 获取一个触发器实例
     * 
     * @param Object $passivity 触发对象
     * @param array $before 执行方法前的操作，每个元素的键名是方法名，值是is_callable可以调用的方法
     * 触发器会把调用方法时的参数同样的传进这个方法
     * @param array $after 执行方法后的操作，每个元素的键名是方法名，值是is_callable可以调用的方法
     * 触发器会把调用方法后的结果同样的传进这个方法
     * @return \Melon\Util\Trigger
     */
    final static public function trigger( $passivity, $before = array(), $after = array() ) {
        return new Util\Trigger( $passivity, $before, $after );
    }
}

// 创建Melon的快捷方式
class M extends Melon { }
