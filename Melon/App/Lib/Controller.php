<?php

namespace Melon\App\Lib;

use Melon\Util;

abstract class Controller {
	
	protected $_request;
	
	protected $_response;
	
	protected $_view;

	public function __construct() {
		$this->_request = \Melon::httpRequest();
		$this->_response = \Melon::httpResponse();
		
		$this->_view = \Melon::template();
		$this->_view->setTemplateDir( \Melon::env( 'appDir' ) . DIRECTORY_SEPARATOR . 'Module' .
			DIRECTORY_SEPARATOR . '_' . \Melon::env( 'moduleName' ) . DIRECTORY_SEPARATOR . 'View' );
		$this->_view->setCompileDir( \Melon::env( 'appDir' ) . DIRECTORY_SEPARATOR .
			'Data' . DIRECTORY_SEPARATOR . 'TplCache' );
	}
}