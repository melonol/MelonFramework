<?php

namespace App1\Module;

class Mod2 extends \Melon\App\Lib\Module {
	
	public function __construct() {
		$this->_name = 'Mod2';
	}
	
	public function getController( $controller ) {
		$controllerName = "App1\Module\_Mod2\Controller\\" . $controller;
		return new $controllerName();
	}
}