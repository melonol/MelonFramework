<?php

namespace Melon\Base;

use \Melon\Exception;

defined('IN_MELON') or die('Permission denied');

class App {
	
	protected $_core;

	public function __construct( Core $core ) {
		$this->_core = $core;
	}
	
	public function init( $config ) {
		$nameRule = '/^[a-zA-Z_]+\w*$/';
		$appName = ( isset( $config['appName'] ) && $config['appName'] ?
				$config['appName'] : null );
		$moduleName = ( isset( $config['moduleName'] ) && $config['moduleName'] ?
				$config['moduleName'] : null );
		if( ! preg_match( $nameRule, $appName ) ) {
			throw new Exception\RuntimeException( '应用名称必需为字母开头，并由字母、数字或下划线组成' );
		}
		if( ! preg_match( $nameRule, $appName ) ) {
			throw new Exception\RuntimeException( '模块名称必需为字母开头，并由字母、数字或下划线组成' );
		}
		$this->_core->env['appName'] = ucfirst( $appName );
		$this->_core->env['moduleName'] = ucfirst( $moduleName );
		$className = $this->_core->env['appName'];
		$this->_core->env['className'] = $this->_core->env['appName'];
		$this->_core->env['appDir'] = $this->_core->env['root'] . DIRECTORY_SEPARATOR . $this->_core->env['className'];
	}
	
	public function run() {
		if( $this->_core->env['runType'] !== 'app' ) {
			throw new Exception\RuntimeException( '当前模式不能运行APP' );
		}
		// 创建目录
		
		$this->_core->load( __FILE__, $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . $this->_core->env['className'] . '.php' );
		
		// 取得路由配置，然后解释它
		$routeConf = $this->_core->acquire( __FILE__, $this->_core->env['appDir'] . DIRECTORY_SEPARATOR .
			'Conf' . DIRECTORY_SEPARATOR . 'Route.php' );
		$route = \Melon::httpRoute( $routeConf );
		$_pathInfo = array();
		$route->parse( $_pathInfo );

		// 整理一下
		$pathInfo = array(
			'controller' => ( isset( $_pathInfo[0] ) ? $_pathInfo[0] :
				isset( $routeConf['defaultController'] ) ? $routeConf['defaultController'] : null ),
			'action' => ( isset( $_pathInfo[1] ) ? $_pathInfo[1] :
				isset( $routeConf['defaultAction'] ) ? $routeConf['defaultAction'] : null ),
			'args' => ( isset( $_pathInfo[2] ) ? array_splice( $_pathInfo, 2 ) : array() ),
		);
		// 搞定后清理掉不再用的数据
		unset( $routeConf, $_pathInfo );

		// 现在把控制权交给当前请求的模块
		$moduleClass = $this->_core->env['className'] . '\Module\\' . $this->_core->env['moduleName'];
		$command = new $moduleClass();
		$command->execute( $pathInfo['controller'], $pathInfo['action'], $pathInfo['args'] );
	}
	
	public function create() {
		
	}
	
	public function createModule() {
		
	}
	
	private function _createFile() {
		
	}
}