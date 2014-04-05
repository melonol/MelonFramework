<?php

namespace __APPNAME__\Module;

class __MODULENAME__ extends \Melon\App\Lib\Module {
	
	public function __construct() {
		$this->_name = '__MODULENAME__';
	}
	
	public function getController( $controller ) {
		$controllerName = "__APPNAME__\Module\___MODULENAME__\Controller\\" . $controller;
		return new $controllerName();
	}
}