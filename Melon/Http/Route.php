<?php

namespace Melon\Request;

defined('IN_MELON') or die('Permission denied');

class Response {

	private $_config = array();
	private $_controller;
	private $_method;
	private $_params;

	/**
	 * 构造器，实例化时请提供相关配置参数
	 * 配置参数格式如下：
	 * array(
	 *      'default_controller' => '', //默认控制器
	 *      'default_method' => '', //默认方法
	 *      '404' => '', //404页面
	 *      'rules' => array(
	 *          'exp' => 'replace', // '正则' => '替换路径'
	 *          ...
	 *      )
	 * )
	 * 路由器会优先解析APP配置（如果它存在），没有匹配项时才解析公共配置
	 * 
	 * @param array $global_config 全局路由配置
	 * @param array $app_config 默认路由配置
	 */
	public function __construct($global_config, $app_config) {
		$global_config = is_array($global_config) ? $global_config : array();
		$app_config = is_array($app_config) ? $app_config : array();
		$this->_config = $this->_mergeConfig($global_config, $app_config);
	}

	/**
	 * 合并配置文件
	 * 为数组提供简单的合并支持，类似array_merge，但本方法可以合并多维数组
	 * 目前只支持两个数组合并
	 * 规则是如果后者拥有前者的相同键值的元素，且值不为空，则替换前者的值
	 * 
	 * @param array $global_config
	 * @param array $app_config
	 * @return array
	 */
	private function _mergeConfig($global_config, $app_config) {
		$config = $app_config;
		foreach ($global_config as $key => $value) {
			if (is_array($value)) {
				if (!isset($app_config[$key])) {
					$app_config[$key] = array();
				}
				//递归合并
				$config[$key] = $this->_mergeConfig($global_config[$key], $app_config[$key]);
			} elseif (!isset($app_config[$key])) {
				$config[$key] = $value;
			}
		}
		return $config;
	}

	/**
	 * 解析路由规则
	 * 
	 * @return boolean 
	 */
	public function parse() {
		$path = $this->getPathInfo();
		$path = empty($path) ? '' : trim($path, '/');
		$replace_path = $path;
		//循环配置文件的规则进行匹配和替换
		if (is_array($this->_config['rules'])) {
			foreach ($this->_config['rules'] as $exp => $replace) {
				$matchs = array();
				$count = 0;
				$replace_path = preg_replace("#{$exp}#i", $replace, $path, -1, $count);
				//有任何一项匹配成功，马上返回
				if ($count > 0) {
					break;
				}
			}
		}
		//解析路径，前两个参数会被认为是控制器（contorller）和方法（method）
		$path_info = $this->_parsePathInfo($replace_path);
		//使用array_shift取数组值，取完后剩下的值就是参数（params）了
		$contorller = isset($path_info[0]) ? array_shift($path_info) : $this->_config['default_controller'];
		$method = isset($path_info[0]) ? array_shift($path_info) : $this->_config['default_method'];

		if (empty($contorller) || empty($method)) {
			return false;
		}
		$this->_setController($contorller);
		$this->_setMethod($method);
		$this->_setParams($path_info);
		return true;
	}

	/**
	 * 如果路径不合法或者无法被解释，可以使用此方法将所有参数设为404的配置（需要在配置里设定，否则无法生效）
	 */
	public function to404Error() {
		$path_info = $this->_parsePathInfo($this->_config['404']);
		$this->_setController(isset($path_info[0]) ? array_shift($path_info) : null);
		$this->_setMethod(isset($path_info[0]) ? array_shift($path_info) : null);
		$this->_setParams($path_info);
	}

	public function getPathInfo() {
		static $path_info = null;
		if (!is_null($path_info)) {
			return $path_info;
		}
		$path_info = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
		$match = array();
		//如果服务器不支持PATH_INFO，则使用REQUEST_URI解析
		if (empty($path_info) &&
				stripos($_SERVER['REQUEST_URI'], '.php?') === false &&
				preg_match("#^[^?]+#", $_SERVER['REQUEST_URI'], $match)) {
			//替换多余的 / 号，并且去掉第一层目录，第一层目录是APP
			$path_info = preg_replace(
					array('#/+#', '#^/?\w+#'), array('/', ''), $match[0]
			);
		}
		return $path_info;
	}

	/**
	 * 解析路径信息
	 * 路径以/号分割为数组，同时两端的/号将会被忽略
	 * 
	 * @param string $path
	 * @return 
	 */
	private function _parsePathInfo($path = '') {
		$path_info = array();
		if (!empty($path)) {
			$path_info = explode('/', trim($path, '/'));
		}
		return $path_info;
	}

	/**
	 * 设置控制器
	 * 
	 * @param string $controller
	 */
	private function _setController($controller) {
		$this->_controller = $controller;
	}

	/**
	 * 设置方法
	 * 
	 * @param string $method
	 */
	private function _setMethod($method) {
		$this->_method = $method;
	}

	/**
	 * 设置参数
	 * 
	 * @param array $params
	 */
	private function _setParams($params) {
		$this->_params = $params;
	}

	/**
	 * 获取控制器
	 * 
	 * @return string
	 */
	public function getContorller() {
		return $this->_controller;
	}

	/**
	 * 获取方法
	 * 
	 * @return string
	 */
	public function getMethod() {
		return $this->_method;
	}

	/**
	 * 获取参数
	 * 
	 * @return array
	 */
	public function getParams() {
		return is_array($this->_params) ? $this->_params : array();
	}

}