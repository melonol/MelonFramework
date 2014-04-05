<?php

namespace __APPNAME__\Module\__PRIVATE_PRE____MODULENAME__\Controller;

class Index extends \Melon\App\Lib\Controller {
	
	public function index() {
		$this->_view->display( 'hello.html' );
	}
}