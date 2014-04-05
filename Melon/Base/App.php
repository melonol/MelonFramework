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
		if( $this->_core->env['install'] === 'app' ) {
			$this->_createApp();
		} else if( $this->_core->env['install'] === 'module' ) {
			$this->_createModule();
		}
		
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
		if( ! file_exists( $this->_core->env['root'] . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $moduleClass ) . '.php' ) ) {
			throw new Exception\RuntimeException( "{$this->_core->env['moduleName']}模块不存在" );
		}
		$command = new $moduleClass();
		$command->execute( $pathInfo['controller'], $pathInfo['action'], $pathInfo['args'] );
	}
	
	protected function _createApp() {
		if( ! is_writable( $this->_core->env['root'] ) ) {
			throw new Exception\RuntimeException( "app目录{$this->_core->env['root']}不存在或不可写" );
		}
		if( is_dir( $this->_core->env['appDir'] ) && ! $this->_isEmptyDir( $this->_core->env['appDir'] ) ) {
			throw new Exception\RuntimeException( "app目录{$this->_core->env['appDir']}不为空，无法创建" );
		}
		$this->_deleteTempDir();
		$tempDir = $this->_createTempDir( 'App' );
		$this->_copyDir( $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Template', $tempDir );
		$this->_replaceContent( $tempDir, '__APPNAME__', $this->_core->env['appName'] );
		$this->_replaceContent( $tempDir, '__MODULENAME__', $this->_core->env['moduleName'] );
		$this->_copyDir( $tempDir, $this->_core->env['root'] );
		$this->_deleteTempDir();
	}
	
	protected function _createModule() {
		$parentModuleDir = $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . 'Module';
		if( ! is_writable( $parentModuleDir ) ) {
			throw new Exception\RuntimeException( "module根目录{$parentModuleDir}不存在或不可写" );
		}
		$moduleDir = $parentModuleDir . DIRECTORY_SEPARATOR . '_' . $this->_core->env['moduleName'];
		if( is_dir( $moduleDir ) ) {
			throw new Exception\RuntimeException( "module目录{$moduleDir}已存在，无法创建" );
		}
		$this->_deleteTempDir();
		$tempDir = $this->_createTempDir( 'Module' );
		$this->_copyDir( $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . '__APPNAME__' . DIRECTORY_SEPARATOR . 'Module', $tempDir );
		$this->_replaceContent( $tempDir, '__APPNAME__', $this->_core->env['appName'] );
		$this->_replaceContent( $tempDir, '__MODULENAME__', $this->_core->env['moduleName'] );
		$this->_copyDir( $tempDir, $parentModuleDir );
		$this->_deleteTempDir();
	}
	
	private function _createTempDir( $subDirName ) {
		$tempDir = $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'InstallTemp' . DIRECTORY_SEPARATOR . $subDirName;
		if( ! is_dir( $tempDir ) ) {
			mkdir( $tempDir, 0777, true );
		}
		return $tempDir;
	}
	
	private function _deleteTempDir() {
		$tempDir = $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'InstallTemp';
		unlink( $tempDir );
	}
	
	private function _isEmptyDir( $dir ) {
		return ( count( scandir( $dir ) ) <= 2 );
	}
	
	private function _copyDir( $source, $destination ) {
		if( ! is_dir( $destination ) ) {
			mkdir( $destination, 0777 );
		}
		$handle = dir( $source );
		while( $entry = $handle->read() ) {
			if( ( $entry != "." ) && ( $entry != ".." ) ) {
				if( is_dir( $source . DIRECTORY_SEPARATOR . $entry ) ) {
					$this->_copyDir( $source . DIRECTORY_SEPARATOR . $entry, $destination . DIRECTORY_SEPARATOR . $entry );
				}
				else {
					copy( $source . DIRECTORY_SEPARATOR . $entry, $destination . DIRECTORY_SEPARATOR . $entry );
				}
			}
		}
	}

	private function _replaceContent( $target, $keyWord, $replace ) {
		$handle = dir( $target );
		while( $entry = $handle->read() ) {
			if( ( $entry != "." ) && ( $entry != ".." ) ) {
				$replacedEntry = str_replace( $keyWord, $replace, $entry );
				$path = $target . DIRECTORY_SEPARATOR . $replacedEntry;
				rename( $target . DIRECTORY_SEPARATOR . $entry, $path );
				if( is_dir( $path ) ) {
					$this->_replaceContent( $path, $keyWord, $replace );
				}
				else {
					$contents = file_get_contents( $path );
					$replacedContents = str_replace( $keyWord, $replace, $contents );
					file_put_contents( $path, $replacedContents );
				}
			}
		}
	}
}