<?php

namespace Melon\Request;

defined('IN_MELON') or die('Permission denied');

class Request {
	
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
	const METHOD_HEAD = 'HEAD';
    const METHOD_PATCH = 'PATCH';
    const METHOD_OPTIONS = 'OPTIONS';
	
	private function __construct() {
		;
	}

	public function getInstance() {
		
	}
	
	/**
	 * 解释HTTP头参数
	 * 所有参数名的'-'号都会被转为'_'，字母转为大写
	 * 然后会被放到self::$_header中
	 */
	private static function _praseHeader() {
		$header = array();
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
		
		if(isset($header['AUTHORIZATION'])) {
			$match = array();
			if (preg_match('/^\w+/', $header['AUTHORIZATION'], $match)) {
				$header['AUTH_TYPE'] = strtoupper( $match[0] );
			}
		}

		//格式化参数
		foreach ($header as $key => $value) {
			$key = strtoupper(str_replace('_', '-', $key));
			self::$_header[$key] = $value;
		}
	}
	
	public function parseAuth($authorization) {
		$authArgs = $matchArgs = array();
		if (preg_match_all('/(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))/', $authorization, $matchArgs)) {
			foreach ($matchArgs[1] as $index => $key) {
				$authArgs[$key] = $matchArgs[2][$index];
			}
		}
		return $authArgs;
	}
	
    /**
     * 获取头信息
     * 
     * @param string $name [optional] 如果提供此项，则返回相应的头信息，否则返回全部
     * @return string
     */
    public function header($name=null) {
        
    }
    
    /**
     * 获取请求方法
     * 
     * @return string
     */
    public function getMethod() {
        return strtoupper( $_SERVER['REQUEST_METHOD'] );
    }
    
    /**
     * 获取请求数据
     * 
     * @staticvar array $data
     * @return string
     */
    public function getData() {
        static $data = array();
        switch ($this->getMethod()) {
            case self::METHOD_GET:
                $data = $_GET;
                break;
            case self::METHOD_POST:
                $data = $_POST;
                break;
            case self::METHOD_PUT:
                parse_str(file_get_contents('php://input'), $put_vars);
                $data = $put_vars;
                break;
        }
        return $data;
    }
	
	public function post() {
		
	}
	
	public function get() {
		
	}
	
	public function put() {
		
	}
	
	public function isPost() {
		
	}
	
	public function isGet() {
		
	}
	
	public function isPut() {
		
	}
	
	public function isHead() {
		
	}
	
	public function isPatch() {
		
	}
	
	public function isOptions() {
		
	}
}