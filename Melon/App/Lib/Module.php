<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://git.oschina.net/397574898/MelonFramework
 * @author Melon <denglh1990@qq.com>
 * @version 0.1.0
 */

namespace Melon\App\Lib;

use Melon\App\Lib;

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
			throw \Melon::throwException( '请提供有效的控制器对象' );
		}
		// TODO::抛出404
		if( ! is_callable( array( $controllerObj, $action ) ) ) {
			throw \Melon::throwException( '控制器对象不存在' );
		}
		$before = $after = array();
		$ucfirstOfAction = ucfirst( $action );
		if( method_exists( $controllerObj, 'before' . $ucfirstOfAction ) ) {
			$before[ $action ] = array( $controllerObj, 'before' . $ucfirstOfAction );
		}
		if( method_exists( $controllerObj, 'after' . $ucfirstOfAction ) ) {
			$after[ $action ] = array( $controllerObj, 'after' . $ucfirstOfAction );
		}
		$controlTrigger = \Melon::trigger( $controllerObj, $before, $after );
		call_user_func( array( $controlTrigger, $action ), $args );
	}
	
	/**
	 * 
	 * 由于框架的载入脚本权限问题，需要模块自身去实例控制器
	 * 
	 * @param string $controller 控制器名字
	 * @return $controllerObj 控制器对象
	 */
	abstract public function getController( $controller );
}
