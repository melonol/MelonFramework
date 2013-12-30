<?php

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

class Request {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_HEAD = 'HEAD';
	const METHOD_PATCH = 'PATCH';
	const METHOD_OPTIONS = 'OPTIONS';
	
	private $_headers = array();

	private $_inputs = array();
	
	private $_method = '';

	private function __construct() {
		$this->_praseHeader();
		$this->_setMethod();
		$this->_setInputs();
	}

	static public function getInstance() {
		static $instance = null;
		if(is_null($instance)) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * 解释HTTP头参数
	 * 所有参数名的'-'号都会被转为'_'，字母转为大写
	 * 然后会被放到self::$_header中
	 */
	private function _praseHeader() {
		$header =& $this->_headers;
		// 这是apache特有的函数，可以很方便取到数据
		if (function_exists('getallheaders')) {
			$header = getallheaders();
		}
		// 其它服务器，在$_SERVER里取，有点麻烦
		// 我用$_SERVER来得到所有http请求头
		// 参考了http://www.oschina.net/question/54100_38761
		else {
			foreach ($_SERVER as $key => $value) {
				if ('HTTP_' == substr($key, 0, 5)) {
					$header[substr($key, 5)] = $value;
				}
			}
		}
		$_header = array();
		//格式化参数
		foreach ($header as $key => $value) {
			$key = strtoupper(str_replace('-', '_', $key));
			$_header[$key] = $value;
		}
		$header = $_header;

		if (isset($_SERVER['CONTENT_LENGTH'])) {
			$header['CONTENT_LENGTH'] = $_SERVER['CONTENT_LENGTH'];
		}
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$header['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
		}

		if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
			$header['PHP_AUTH_DIGEST'] = $_SERVER['PHP_AUTH_DIGEST'];
		} elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$header['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
			$header['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
		}

		if (isset($header['AUTHORIZATION'])) {
			$match = array();
			if (preg_match('/^\w+/', $header['AUTHORIZATION'], $match)) {
				$header['AUTH_TYPE'] = $match[0];
			}
		}
	}

	public function parseAuth() {
		$authArgs = $matchArgs = array();
		if( $this->header( 'AUTHORIZATION' ) ) {
			if (preg_match_all('/(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))/', $this->header( 'AUTHORIZATION' ), $matchArgs)) {
				foreach ($matchArgs[1] as $index => $key) {
					if($matchArgs[2][$index]) {
						$authArgs[$key] = $matchArgs[2][$index];
					} else {
						$authArgs[$key] = $matchArgs[3][$index];
					}
				}
			}
		}
		return $authArgs;
	}

	/**
	 * 获取请求数据
	 * 
	 * @staticvar array $data
	 * @return string
	 */
	private function _setInputs() {
		$this->_inputs['get'] =& $_GET;
		$this->_inputs['post'] =& $_POST;
		$this->_inputs['cookie'] =& $_COOKIE;
		$this->_inputs['put'] = array();
		if ($this->isPut()) {
			$putVars =& $this->_inputs['put'];
			parse_str(file_get_contents('php://input'), $putVars);
		}
		$this->_inputs['request'] =& $_REQUEST;
		// 虽然5.3默认已经关闭magic quotes，但5.4才真正移除
		// 所以还是要处理这个问题
		if(get_magic_quotes_gpc()) {
			foreach($this->_inputs as &$data) {
				foreach($data as &$value) {
					$value = stripslashes($value);
				}
			}
		}
	}
	
	private function _setMethod() {
		if(isset($_SERVER['REQUEST_METHOD'])) {
			$this->_method = strtoupper($_SERVER['REQUEST_METHOD']);
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
	 * 获取头信息
	 * 
	 * @param string $name
	 * @return string
	 */
	public function header($name) {
		return ( isset( $this->_headers[ $name ] ) ? $this->_headers[ $name ] : null );
	}
	
	public function inputs() {
		return $this->_inputs;
	}
	
	public function input($key, $mode='a') {
		static $map = array(
			self::METHOD_GET => 'g',
			self::METHOD_POST => 'p',
			self::METHOD_PUT => 'p'
		);
		// auto
		if($mode == 'a') {
			$_mode = (isset($map[$this->method()]) ? $map[$this->method()] : 'r');
		} else {
			$_mode = $mode;
		}
		switch($_mode) {
			// get
			case 'g' :
				$inputs =& $this->_inputs['get'];
				break;
			// put or post
			case 'p' :
				if ($this->isPut() ) {
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
				if( isset( $this->_inputs['put'][$key] ) ) {
					$inputs =& $this->_inputs['put'];
				} else {
					$inputs =& $this->_inputs['request'];
				}
				break;
		}
		return isset( $inputs[$key] ) ? $inputs[$key] : null;
	}
	
	/**
	 * 获取并格式化输入参数
	 * 
	 * 由于输入参数被预定义程序处理，所以使用此方法得到的数据不保证完全可靠
	 * 如果你需要确切的数据，请使用input方法获取并自行处理
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @param mixed $type
	 * @param string $mode
	 */
	public function inputFormat($key, $default=null, $type='str', $mode='a') {
		//bool  str  int  double  float posint natint time  intime
		$value = $this->input($key, $mode);
		if(is_null($value)) {
			return $default;
		}
		$_type = (is_array($type) ? 'enum' : $type);
		switch ($_type) {
			case 'bool':		// 布尔值
				$value = ( !!$value && $value != 0 );
				break;
			case 'int':			// 整数
				$value = intval($value);
				break;
			case 'float':		// 浮点数
				$value = floatval($value);
			case 'double':		// 双精度浮点数
				$value = doubleval($value);
				break;
				break;
			case 'posint':		// 正整数
				$value = intval($value);
				if($value <= 0) {
					$value = $default;
				}
				break;
			case 'natint':		// 自然数（非负整数）
				$value = intval($value);
				if($value < 0) {
					$value = $default;
				}
				break;
			case 'time':		// 时间截
				$timestamp = strtotime($value);
				$value = $timestamp ?: $default;
				break;
			case 'intime':		// 当天起始时间的时间截
				$timestamp = strtotime($value);
				$value = ( $timestamp ? strtotime( date( 'Y-m-d 00:00:00', $timestamp ) ) : $default );
				break;
			case 'endtime':		// 当天结束时间的时间截
				$timestamp = strtotime($value);
				$value = ( $timestamp ? strtotime( date( 'Y-m-d 23:59:59', $timestamp ) ) : $default );
				break;
			case 'enum':		// 枚举
				$value = ( in_array( $value, $type ) ? $value : $default );
				break;
			case 'str':			// 字符串
			default:
				$value = strval($value);
				break;
		}
		return $value;
	}

	public function isGet() {
		return ( $this->method() === self::METHOD_GET );
	}

	public function isPost() {
		return ( $this->method() === self::METHOD_POST );
	}

	public function isPut() {
		return ( $this->method() === self::METHOD_PUT );
	}
	
	public function isDelete() {
		return ( $this->method() === self::METHOD_DELETE );
	}

	public function isHead() {
		return ( $this->method() === self::METHOD_HEAD );
	}

	public function isPatch() {
		return ( $this->method() === self::METHOD_PATCH );
	}

	public function isOptions() {
		return ( $this->method() === self::METHOD_OPTIONS );
	}
}