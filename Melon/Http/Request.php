<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

/**
 * Request负责处理HTTP请求的信息
 * 
 * 使用它可以方便得到HTTP请求的基本信息，包括请求头、参数、方法
 * 并提供了格式化参数功能
 * 由于一次请求的值是固定的，所以我设置为单例了，开销会比较少
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
class Request {
    
    // 方法组
    const METHOD_GET        = 'GET';
    const METHOD_POST       = 'POST';
    const METHOD_PUT        = 'PUT';
    const METHOD_DELETE     = 'DELETE';
    const METHOD_HEAD       = 'HEAD';
    const METHOD_PATCH      = 'PATCH';
    const METHOD_OPTIONS    = 'OPTIONS';
    
    /**
     * HTTP头数据
     * 
     * @var array
     */
    protected $_headers = array();
    
    /**
     * 请求数据
     * 
     * @var array
     */
    protected $_inputs = array();
    
    /**
     * 请求方法
     * 
     * @var string
     */
    protected $_method = '';

    protected function __construct() {
        $this->_praseHeader();
        $this->_setMethod();
        $this->_setInputs();
    }
    
    /**
     * 获取该类的实例对象
     * 
     * @staticvar Request $instance
     * @return \self
     */
    static public function getInstance() {
        static $instance = null;
        if( is_null( $instance ) ) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * 解释HTTP头参数
     * 所有参数名的'-'号都会被转为'_'，字母转为大写
     * 然后会被放到self::$_header中
     * 
     * @return void
     */
    protected function _praseHeader() {
        $header =& $this->_headers;
        // 这是apache特有的函数，可以很方便取到数据
        if( function_exists( 'getallheaders' ) ) {
            $header = getallheaders();
        }
        // 其它服务器，在$_SERVER里取，有点麻烦
        // 我用$_SERVER来得到所有http请求头
        // 参考了http://www.oschina.net/question/54100_38761
        else {
            foreach( $_SERVER as $key => $value ) {
                if( 'HTTP_' === substr( $key, 0, 5 ) ) {
                    $header[ substr( $key, 5 ) ] = $value;
                }
            }
        }
        $_header = array();
        // 格式化参数
        foreach( $header as $key => $value ) {
            $key = strtoupper( str_replace( '-', '_', $key ) );
            $_header[ $key ] = $value;
        }
        $header = $_header;

        if( isset( $_SERVER['CONTENT_LENGTH'] ) ) {
            $header['CONTENT_LENGTH'] = $_SERVER['CONTENT_LENGTH'];
        }
        if( isset( $_SERVER['CONTENT_TYPE'] ) ) {
            $header['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
        
        // HTTP认证信息
        if( isset( $_SERVER['PHP_AUTH_DIGEST'] ) ) {
            $header['PHP_AUTH_DIGEST'] = $_SERVER['PHP_AUTH_DIGEST'];
        } elseif ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
            $header['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
            $header['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
        }
        // 认证方法
        if( isset( $header['AUTHORIZATION'] ) ) {
            $match = array();
            if ( preg_match( '/^\w+/', $header['AUTHORIZATION'], $match ) ) {
                $header['AUTH_TYPE'] = $match[0];
            }
        }
    }
    
    /**
     * 解释HTTP认证authorization里的参数
     * 
     * @return array
     */
    public function parseAuth() {
        $authArgs = $matchArgs = array();
        if( $this->header( 'AUTHORIZATION' ) ) {
            if( preg_match_all( '/(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))/', $this->header( 'AUTHORIZATION' ), $matchArgs ) ) {
                foreach( $matchArgs[1] as $index => $key ) {
                    if( $matchArgs[2][ $index ] ) {
                        $authArgs[ $key ] = $matchArgs[2][ $index ];
                    } else {
                        $authArgs[ $key ] = $matchArgs[3][ $index ];
                    }
                }
            }
        }
        return $authArgs;
    }
    
    /**
     * 获取客户端IP
     * 
     * 这个方法来源于互联网
     * 
     * @return string
     */
    public function ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
		if( isset( $_SERVER['HTTP_CLIENT_IP'] )
                && preg_match( '/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) &&
            preg_match_all( '#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches ) ) {
			foreach ( $matches[0] as $xip ) {
				if( ! preg_match( '#^(10|172\.16|192\.168)\.#', $xip ) ) {
					$ip = $xip;
					break;
				}
			}
		}
		return $ip;
    }
    
    /**
     * 设置请求数据，保存到类属性里面
     * 
     * 数据会被去掉magic quotes添加的转义符
     * 
     * @return void
     */
    protected function _setInputs() {
        $this->_inputs['get'] =& $_GET;
        $this->_inputs['post'] =& $_POST;
        $this->_inputs['cookie'] =& $_COOKIE;
        $this->_inputs['put'] = array();
        if ( $this->isPut() ) {
            $putVars =& $this->_inputs['put'];
            parse_str( file_get_contents( 'php://input' ), $putVars );
        }
        $this->_inputs['request'] =& $_REQUEST;
        
        // 虽然5.3默认已经关闭magic quotes，但5.4才真正移除
        // 所以还是要处理这个问题
        if( function_exists( 'get_magic_quotes_gpc' ) && get_magic_quotes_gpc() ) {
            foreach( $this->_inputs as &$data ) {
                foreach( $data as &$value ) {
                    $value = stripslashes( $value );
                }
            }
        }
    }
    
    /**
     * 设置请求方法，将其转为大写
     * 
     * @return void
     */
    protected function _setMethod() {
        if( isset( $_SERVER['REQUEST_METHOD'] ) ) {
            $this->_method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        }
    }

    /**
     * 获取请求方法
     * 
     * @return string
     */
    public function method() {
        return $this->_method;
    }
    
    /**
     * 获取所有头信息
     */
    public function headers() {
        return $this->_headers;
    }

    /**
     * 获取指定头信息
     * 
     * @param string $name 名称，字母请统一使用大写， - 线改为 _ 线
     * @return string|null 不存在会返回null
     */
    public function header( $name ) {
        return ( isset( $this->_headers[ $name ] ) ? $this->_headers[ $name ] : null );
    }
    
    /**
     * 获取所有请求参数
     * 
     * @return array
     */
    public function inputs() {
        return $this->_inputs;
    }
    
    /**
     * 获取指定请求参数
     * 
     * @staticvar array $map 模式
     * @param string $key 参数名
     * @param string $mode 获取参数方式，即从哪个方法里找
     * 1. a    [默认] auto，自动在当前方法里找
     * 2. g    get
     * 3. p    post or put
     * 4. c    cookie
     * 5. r    request
     * 
     * @return mixed
     */
    public function input( $key, $mode='a' ) {
        static $map = array(
            self::METHOD_GET => 'g',
            self::METHOD_POST => 'p',
            self::METHOD_PUT => 'p'
        );
        // auto
        if($mode == 'a') {
            $_mode = ( isset( $map[ $this->method() ] ) ? $map[ $this->method() ] : 'r' );
        } else {
            $_mode = $mode;
        }
        switch( $_mode ) {
            // get
            case 'g' :
                $inputs =& $this->_inputs['get'];
                break;
            // put or post
            case 'p' :
                if( $this->isPut() ) {
                    $inputs =& $this->_inputs['put'];
                } else {
                    $inputs =& $this->_inputs['post'];
                }
                break;
            // cookie
            case 'c' :
                $inputs =& $this->_inputs['cookie'];
                break;
            // request or default
            case 'r' :
            default :
                if( isset( $this->_inputs['put'][ $key ] ) ) {
                    $inputs =& $this->_inputs['put'];
                } else {
                    $inputs =& $this->_inputs['request'];
                }
                break;
        }
        return isset( $inputs[ $key ] ) ? $inputs[ $key ] : null;
    }
    
    /**
     * 获取并格式化输入参数
     * 
     * 由于输入参数被预定义程序处理，所以使用此方法得到的数据不保证完全可靠
     * 如果你需要确切的数据，请使用input方法获取并自行处理
     * 
     * @param string $key 参数名
     * @param mixed $default 当参数不存在时默认采用的值，无论type指定为哪种格式，这个值不会被程序做任何处理，会直接被返回
     * @param mixed $type 格式化类型，一般情况下是字符串，当为数组时，会被认为是枚举类型enum
     * 1. str             [默认] 字符串
     * 2. bool            布尔值，字符串的0会被当作假（false），强制格式化为此类型
     * 3. int             整数，强制格式化为此类型
     * 4. float           浮点数，强制格式化为此类型
     * 5. double          双精度浮点数，强制格式化为此类型
     * 6. posint          正整数，当参数值不符条件时返回default
     * 7. natint          自然数（非负整数），当参数值不符条件时返回default
     * 8. time            时间截，当参数值无法被格式化时返回default
     * 9. intime          当天起始时间的时间截，当参数值无法被格式化时返回default
     * 10. endtime        当天结束时间的时间截，精确到23时59分59秒，当参数值无法被格式化时返回default
     * 11. enum           枚举型，注意枚举不能显式的写enum，必需传一个数组作为枚举值，当参数值不符条件时返回default
     * 
     * @param string $mode 获取参数方式，即从哪个方法里找
     * 1. a    [默认] auto，自动在当前方法里找
     * 2. g    get
     * 3. p    post or put
     * 4. c    cookie
     * 5. r    request
     */
    public function inputFormat( $key, $default=null, $type='str', $mode='a' ) {
        $value = $this->input( $key, $mode );
        if( is_null( $value ) ) {
            return $default;
        }
        $_type = ( is_array( $type ) ? 'enum' : $type );
        switch( $_type ) {
            case 'bool':        // 布尔值
                $value = ( !!$value && $value != 0 );
                break;
            case 'int':         // 整数
                $value = intval( $value );
                break;
            case 'float':       // 浮点数
                $value = floatval( $value );
            case 'double':      // 双精度浮点数
                $value = doubleval( $value );
                break;
                break;
            case 'posint':      // 正整数
                $value = intval( $value );
                if( $value <= 0 ) {
                    $value = $default;
                }
                break;
            case 'natint':      // 自然数（非负整数）
                $value = intval( $value );
                if( $value < 0 ) {
                    $value = $default;
                }
                break;
            case 'time':        // 时间截
                $timestamp = strtotime( $value );
                $value = $timestamp ?: $default;
                break;
            case 'intime':      // 当天起始时间的时间截
                $timestamp = strtotime( $value );
                $value = ( $timestamp ? strtotime( date( 'Y-m-d 00:00:00', $timestamp ) ) : $default );
                break;
            case 'endtime':     // 当天结束时间的时间截
                $timestamp = strtotime( $value );
                $value = ( $timestamp ? strtotime( date( 'Y-m-d 23:59:59', $timestamp ) ) : $default );
                break;
            case 'enum':        // 枚举
                $value = ( in_array( $value, $type ) ? $value : $default );
                break;
            case 'str':         // 字符串
            default:
                $value = strval( $value );
                break;
        }
        return $value;
    }
    
    /**
     * 当前请求是否是使用get方法
     * 
     * @return boolean
     */
    public function isGet() {
        return ( $this->method() === self::METHOD_GET );
    }

    /**
     * 当前请求是否是使用post方法
     * 
     * @return boolean
     */
    public function isPost() {
        return ( $this->method() === self::METHOD_POST );
    }

    /**
     * 当前请求是否是使用put方法
     * 
     * @return boolean
     */
    public function isPut() {
        return ( $this->method() === self::METHOD_PUT );
    }
    
    /**
     * 当前请求是否是使用delete方法
     * 
     * @return boolean
     */
    public function isDelete() {
        return ( $this->method() === self::METHOD_DELETE );
    }

    /**
     * 当前请求是否是使用head方法
     * 
     * @return boolean
     */
    public function isHead() {
        return ( $this->method() === self::METHOD_HEAD );
    }

    /**
     * 当前请求是否是使用patch方法
     * 
     * @return boolean
     */
    public function isPatch() {
        return ( $this->method() === self::METHOD_PATCH );
    }

    /**
     * 当前请求是否是使用options方法
     * 
     * @return boolean
     */
    public function isOptions() {
        return ( $this->method() === self::METHOD_OPTIONS );
    }
}