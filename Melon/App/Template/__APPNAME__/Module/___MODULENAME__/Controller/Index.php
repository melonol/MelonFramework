<?php

namespace __APPNAME__\Module\___MODULENAME__\Controller;

class Index extends \Melon\App\Lib\Controller {
	
	public function index() {
		$this->_view->display( 'hello.html' );
	}
}