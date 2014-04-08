<?php

namespace __APPNAME__\Module\__PRIVATE_PRE____MODULENAME__\Controller;

use __APPNAME__\Module\__PRIVATE_PRE____MODULENAME__\Model;

/**
 * 控制器例子
 */
class Index extends \Melon\App\Lib\Controller {
	
	public function index() {
		// 实例一个模型
		// 这只是个例子，你需要先配置数据库和创建member表才能运行下面这代码
		// $memberModel = new Model\Member( 'member' );
		
		print_r( \__APPNAME__::packageAcquire( 'Lang/Comment.php' ) );
		
		// 显示视图
		$this->_view->display( 'hello.html' );
	}
}