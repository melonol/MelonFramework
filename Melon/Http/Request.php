<?php

namespace Melon\Request;

defined('IN_MELON') or die('Permission denied');

class Request {

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
		
		if(isset($header['AUTHENTICATION'])) {
			$match = array();
			if (preg_match('/^\w+/', $header['AUTHENTICATION'], $match)) {
				$header['PHP_AUTH_TYPE'] = strtoupper( $match[0] );
				// apache下有AUTH_TYPE参数，为其它服务器做个兼容
				$header['AUTH_TYPE'] = $header['PHP_AUTH_TYPE'];
				
				$header['PHP_AUTH_ARGS'] = $matchArgs = array();
				if (preg_match_all('/(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))/', $header['AUTHENTICATION'], $matchArgs)) {
					foreach ($matchArgs[1] as $index => $key) {
						$header['PHP_AUTH_ARGS'][$key] = $matchArgs[2][$index];
					}
				}
			}
		}

		//格式化参数
		foreach ($header as $key => $value) {
			$key = strtoupper(str_replace('_', '-', $key));
			self::$_header[$key] = $value;
		}
	}
}