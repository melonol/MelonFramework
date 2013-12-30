<?php

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

class SimpleRest {
	
	const MATCH_ALL = 0;
	const MATCH_ONE = 1;
	
	private $_matchMode;
	
	private $_route;
	
	private $_method;
	
	private $_matchTotal = 0;
	
	public function __construct(Route $route, $matchMode = self::MATCH_ONE) {
		$this->_route = $route;
		$this->_matchMode = ( $matchMode === self::MATCH_ALL ? self::MATCH_ALL : self::MATCH_ONE );
		$this->_method = strtolower( \Melon::HttpRequest()->method() );
	}
	
	private function _parse($method, $rule, $callback) {
		if($method !== $this->_method || !$rule ||
			($this->_matchMode === self::MATCH_ONE && $this->_matchTotal > 0)) {
			return;
		}
		$parseInfo = array();
		$this->_route->setConfig(array(
			$method => array(
				$rule => 'lucky'
			)
		))->parse($parseInfo);
		if( $parseInfo ) {
			$this->_matchTotal++;
			call_user_func_array($callback, $parseInfo['args']);
		}
	}
	
	public function get($rule, $callback) {
		$this->_parse('get', $rule, $callback);
	}
	
	public function post($rule, $callback) {
		$this->_parse('post', $rule, $callback);
	}

	public function put($rule, $callback) {
		$this->_parse('put', $rule, $callback);
	}
	
	public function delete($rule, $callback) {
		$this->_parse('delete', $rule, $callback);
	}

	public function head($rule, $callback) {
		$this->_parse('head', $rule, $callback);
	}

	public function patch($rule, $callback) {
		$this->_parse('patch', $rule, $callback);
	}

	public function options($rule, $callback) {
		$this->_parse('options', $rule, $callback);
	}
	
	public function matchTotal() {
		return $this->_matchTotal;
	}
}