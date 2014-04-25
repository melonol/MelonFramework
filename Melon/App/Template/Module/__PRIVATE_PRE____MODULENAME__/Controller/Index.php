<?php

namespace __APPNAME__\Module\__PRIVATE_PRE____MODULENAME__\Controller;

/**
 * 控制器例子
 */
class Index extends \Melon\App\Lib\Controller {
    
    public function index() {
        $this->view->display( 'hello.html' );
    }
}