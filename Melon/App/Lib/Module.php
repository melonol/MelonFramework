<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.0
 */

namespace Melon\App\Lib;

use Melon\Util;

defined('IN_MELON') or die('Permission denied');

/**
 * APP模块入口
 * 
 * APP的MVC模式主要逻辑是在这里实现的，由于它是一个接口，所以你很容易针对自身的业务需求做改变
 */
abstract class Module {
	
	/**
	 * 运行MVC
	 * 
	 * 它会执行控制器下的方法，并使用触发器为这个方法绑定 'before方法名'和'after方法名'两个方法
	 * 类似AOP模式，它们分别在当前方法执行前和执行后分别被触发（执行）
	 * 前提是你需要在这个控制器下声明这两个方法，程序不会自己声明
	 * 
	 * @param string $controller 控制器
	 * @param string $action 方法
	 * @param array $args 参数
	 */
	public function execute( $controller, $action, array $args = array() ) {
		$controllerObj = $this->getController( $controller );
		
		if( ! is_object( $controllerObj ) ) {
			trigger_error( "控制器{$controller}不存在", E_USER_ERROR );
			$this->page404();
		}
		if( ! is_callable( array( $controllerObj, $action ) ) ) {
			trigger_error( "控制器方法{$action}不存在", E_USER_ERROR );
			$this->page404();
		}
		
		// 为控制器设置属性
		$controllerObj->request = \Melon::httpRequest();
		$controllerObj->response = \Melon::httpResponse();
		$controllerObj->lang = new Lang( $this->getCommentLang() );
		$controllerObj->view = new View( $controllerObj );
		
		$before = $after = array();
		$ucfirstOfAction = ucfirst( $action );
		if( method_exists( $controllerObj, 'before' . $ucfirstOfAction ) ) {
			$before[ $action ] = array( $controllerObj, 'before' . $ucfirstOfAction );
		}
		if( method_exists( $controllerObj, 'after' . $ucfirstOfAction ) ) {
			$after[ $action ] = array( $controllerObj, 'after' . $ucfirstOfAction );
		}
		$controlTrigger = \Melon::trigger( $controllerObj, $before, $after );
		call_user_func_array( array( $controlTrigger, $action ), $args );
	}
	
	/**
	 * 输出404
	 * 
	 * @return void
	 */
	public function page404() {
		\Melon::load( \Melon::env( 'appDir' ) . DIRECTORY_SEPARATOR . \Melon::env( 'routeConfig.404' ) );
		\Melon::halt();
	}
	
	/**
	 * 
	 * 由于框架的载入脚本权限问题，需要模块自身去实例控制器
	 * 
	 * @param string $controller 控制器名字
	 * @return $controllerObj 控制器对象
	 */
	abstract public function getController( $controller );
	
	/**
	 * 
	 * 由于框架的载入脚本权限问题，需要模块自身去加载公共语言包
	 * 
	 * @return $lang
	 */
	abstract public function getCommentLang();
}
