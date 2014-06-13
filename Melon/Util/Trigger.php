<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Util;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 简单的事件触发器，可以实现一些类似AOP的操作
 * 
 * <pre>
 * 例：
 * class Test {
 *     public function info( $name ) {
 *        $text = 'Hello ' . $name;
 *         echo $text;
 *        return $text;
 *     }
 * }
 * 
 * // 绑定触发事件
 * $testTrigger = new Trigger( new Test(), array(
 *     'info' => function( $arg1 ) {
 *         echo '执行前，参数是：' . $arg1;
 *     }
 * ), array(
 *     'info' => function( $result ) {
 *         echo '执行后，返回结果是：' . $result;
 *     }
 * ) );
 * 
 * $testTrigger->info( 'Melon' );
 * // 输出：
 * // 执行前，参数是：Melon
 * // Hello Melon
 * // 执行后，返回结果是：Hello Melon
 * </pre>
 * 
 * 使用了拦截器对调用方法进行拦截，基本上你可以把触发器当成类（触发对象）本身那样去使用
 * 但它们是有本质上的区别，触发器只是方便进行一些额外操作
 * 强烈建议把触发器和触发对象显式的区分开来，简单的方式是通过命名：
 * $template = new Template();
 * $templateTrigger = new Trigger( $template );
 * 
 * 缺陷：
 * 如果触发对象中定义了__call方法，想直接调用__call是不行的
 * 不过我相信一般情况下你不会这么做
 * 
 * 由于拦截器的隐蔽性，大量使用可能造成程序可读性和维护性下降
 * 如果没有很好的使用规范，建议少使用为好
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
class Trigger {
    
    /**
     * 触发对象
     * 
     * @var Object
     */
    protected $_passivity;
    
    /**
     * 触发对象的名字
     * 
     * @var string
     */
    protected $_className;

    /**
     * 调用方法前执行的方法组
     * 
     * @var array
     */
    protected $_before;
    
    /**
     * 调用方法后执行的方法组
     * 
     * @var array 
     */
    protected $_after;
    
    /**
     * 构造器
     * 
     * @param Object $passivity 触发对象
     * @param array $before 执行方法前的操作，每个元素的键名是方法名，值是is_callable可以调用的方法
     * 触发器会把调用方法时的参数同样的传进这个方法
     * @param array $after 执行方法后的操作，每个元素的键名是方法名，值是is_callable可以调用的方法
     * 触发器会把调用方法后的结果同样的传进这个方法
     */
    public function __construct( $passivity, $before = array(), $after = array() ) {
        if( ! is_object( $passivity ) ) {
            \Melon::throwException( '触发对象必需是有一个有效的实例对象' );
        }
        $this->_className = get_class( $passivity );
        $this->_passivity = $passivity;
        $this->_before = $before;
        $this->_after = $after;
    }
    
    /**
     * 调用方法时的拦截器
     * 
     * @param string $methodName 方法名
     * @param array $arguments 参数
     * @return mixed
     */
    public function __call( $methodName, $arguments ) {
        $result = null;
        if( method_exists( $this->_passivity, $methodName ) && is_callable( array( $this->_passivity, $methodName ) ) ) {
            // 调用前
            if( isset( $this->_before[ $methodName ] ) ) {
                if( ! is_callable( $this->_before[ $methodName ] ) ) {
                    \Melon::throwException( "{$this->_className}类{$methodName}绑定的执行前调用方法并不是可调用的" );
                }
                call_user_func_array( $this->_before[ $methodName ], $arguments );
            }
            
            // 调用中
            $result = call_user_func_array( array( $this->_passivity, $methodName ), $arguments );
            
            // 调用后
            if( isset( $this->_after[ $methodName ] ) ) {
                if( ! is_callable( $this->_after[ $methodName ] ) ) {
                    \Melon::throwException( "{$this->_className}类{$methodName}绑定的执行后调用方法不是可调用的" );
                }
                call_user_func( $this->_after[ $methodName ], $result );
            }
        } else {
            \Melon::throwException( "触发器无法在{$this->_className}类中找到可调用的{$methodName}方法" );
        }
        return $result;
    }
}