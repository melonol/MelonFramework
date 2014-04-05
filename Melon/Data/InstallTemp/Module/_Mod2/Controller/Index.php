<?php

namespace App1\Module\_Mod2\Controller;

class Index extends \Melon\App\Lib\Controller {
	
	public function index() {
		$this->_view->display( 'hello.html' );
	}
}