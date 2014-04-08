<?php

namespace __APPNAME__\Module;

class __MODULENAME__ extends \Melon\App\Lib\Module {
	
	public function getController( $controller ) {
		$controllerName = '__APPNAME__\Module\__PRIVATE_PRE____MODULENAME__\Controller\\' . $controller;
		return new $controllerName();
	}
	
	public function getCommentLang() {
		return \__APPNAME__::acquire( __DIR__ . DIRECTORY_SEPARATOR . '__PRIVATE_PRE____MODULENAME__' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . 'Comment.php' );
	}
}