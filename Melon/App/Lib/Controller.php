<?php

namespace Melon\App\Lib;

use Melon\Util;

/**
 * APP的控制器接口
 * 
 * 它提供了请求、回应和视图的功能
 * 如果你继承了这个控制器，并覆写了构造器
 * 请在构造器中加上parent::__construct();保证基本功能不受影响
 */
abstract class Controller {
	
	/**
	 * 当前请求实例
	 * 
	 * @var \Melon\Http\Request
	 */
	protected $_request;
	
	/**
	 * 回应实例
	 * 
	 * @var \Melon\Http\Response 
	 */
	protected $_response;
	
	/**
	 * 视图实例
	 * 
	 * @var \Melon\Util\Template 
	 */
	protected $_view;

	public function __construct() {
		$this->_request = \Melon::httpRequest();
		$this->_response = \Melon::httpResponse();
		
		// 视图设置好基本的目录，方便管理和使用
		$this->_view = \Melon::template();
		$this->_view->setTemplateDir( \Melon::env( 'appDir' ) . DIRECTORY_SEPARATOR . 'Module' .
			DIRECTORY_SEPARATOR . \Melon::env( 'config.privatePre' ) . \Melon::env( 'moduleName' ) . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . \Melon::env( 'controller' ) );
		$this->_view->setCompileDir( \Melon::env( 'appDir' ) . DIRECTORY_SEPARATOR .
			'Data' . DIRECTORY_SEPARATOR . 'TplCache' );
	}
}