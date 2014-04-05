<?php

namespace __APPNAME__\Module\__MODULENAME__\Controller;

class Index extends \Melon\App\Lib\Controller {
	
	public function index() {
		$this->display( 'hello.html' );
	}
}