<?php

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

class Route {

	private $_config = array();
	
	private $_method;
	
	private $_pathInfo;

	/**
	 * 构造器，实例化时请提供相关配置参数
	 * @param array $config 全局路由配置
	 */
	public function __construct($config = array()) {
		$this->setConfig($config);
		$this->_setPathInfo();
		$this->_method = strtolower( \Melon::httpRequest()->method() );
	}
	
	public function setConfig($config) {
		$this->_config = is_array($config) ? $config : array();
		return $this;
	}
	
	private function _getRules() {
		$rules = isset($this->_config['global']) && is_array($this->_config['global']) ?
			$this->_config['global'] : array();
		if(isset($this->_config[$this->_method]) && is_array($this->_config[$this->_method])) {
			foreach($this->_config[$this->_method] as $exp => $replace) {
				if(isset($rules[$exp])) {
					unset($rules[$exp]);
				}
			}
			$rules = array_merge($this->_config[$this->_method], $rules);
		}
		return $rules;
	}
	
	public function pathInfo() {
		return $this->_pathInfo;
	}
	
	private function _setPathInfo() {
		$pathInfo = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
		//如果服务器不支持PATH_INFO，则使用REQUEST_URI解析
		if (empty($pathInfo) ) {
			$match = array();
			if( isset($_SERVER['REQUEST_URI']) && stripos($_SERVER['REQUEST_URI'], '.php?') === false &&
				substr($_SERVER['REQUEST_URI'], -4) !== '.php' &&
				preg_match("#^[^?]+#", $_SERVER['REQUEST_URI'], $match)) {
				//替换多余的 / 号
				$pathInfo = preg_replace( '#/+#', '/', $match[0] );
			} else {
				$pathInfo = '/';
			}
		}
		$this->_pathInfo = trim( $pathInfo, '/' );
	}
	
	public function parse( &$parseInfo = array() ) {
		$pathInfo = $this->pathInfo();
		foreach ($this->_getRules() as $exp => $replace) {
			$_exp = ( ( $exp[0] === '/' ) ? substr( $exp, 1 ) : $exp );
			if( ! $pathInfo && ! $_exp ) {
				$parseInfo = array(
					'args' => array(),
					'rule' => $exp
				);
				break;
			}
			$expInfo = array();
			$group = array();
			foreach( preg_split( '/(?<!\\\\)\//', $_exp ) as $elem ) {
				$matchGroup = array();
				if( $_exp && $elem[0] === '[' && preg_match( '/^\[(\w+)(?::(.*))?\]$/', $elem, $matchGroup ) ) {
					$group[ $matchGroup[1] ] = "[{$matchGroup[1]}]";
					$elem = ( isset( $matchGroup[2] ) ?
						"(?<{$matchGroup[1]}>{$matchGroup[2]})" : "(?<{$matchGroup[1]}>[^\/]+)" );
				}
				$expInfo[] = $elem;
			}
			$_exp = implode( '/', $expInfo );
			if( preg_match( "#^{$_exp}$#i", $pathInfo, $match ) ) {
				if($group) {
					$replaceList = array();
					foreach($group as $name => $value) {
						$replaceList[] = $match[$name];
					}
					$parseInfo = array(
						'args' => $replaceList,
						'rule' => $exp
					);
					return str_replace($group, $replaceList, $replace);
				} else {
					unset($match[0]);
					$parseInfo = array(
						'args' => $match,
						'rule' => $exp
					);
					return preg_replace("#^{$_exp}$#i", $replace, $pathInfo );
				}
			}
		}
		return $pathInfo;
	}
}