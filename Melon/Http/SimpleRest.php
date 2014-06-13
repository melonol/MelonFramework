<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

/**
 * 正如其名，一个简单的REST封装，引用了Route和Request，Response类
 * 
 * <pre>
 * 例：
 * // 设置各种规则，一旦被匹配到，则马上会执行回调函数
 * // 路由规则请参考Route类
 * 
 * $simpleRest->get('/', function() {
 *    echo '欢迎来到Melon的世界！';
 * });
 * 
 * $simpleRest->post('/[type]/[id]', function( $type, $id ) {
 *    // 获取post参数中data的值
 *    $data = Melon::httpRequest()->input( 'data' );
 * });
 * 
 * $simpleRest->delete('/[type]/[id]', function( $type, $id ) {
 *    ...
 * });
 * 
 * // 如果没有规则被匹配，输出404
 * if( ! $simpleRest->matchTotal() ) {
 *    echo 404;
 * }
 * </pre>
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 */
class SimpleRest {
    
    // 匹配模式
    // 匹配所有符合规则的路由
    const MATCH_ALL = 0;
    // 只匹配第一个符合规则的路由，之后都会被忽略
    const MATCH_ONE = 1;
    
    /**
     * 匹配模式
     * 
     * @var int 
     */
    protected $_matchMode;
    
    /**
     * 路由
     * 
     * @var Melon\Http\Route 
     */
    protected $_route;
    
    /**
     * response
     * 
     * @var Melon\Http\Response
     */
    protected $_response;
    
    /**
     * 当前请求方法
     * 
     * @var string 
     */
    protected $_method;
    
    /**
     * 匹配总数
     * 
     * @var int 
     */
    protected $_matchTotal = 0;
    
    /**
     * 
     * @param \Melon\Http\Route $route
     * @param \Melon\Http\Request $request
     * @param \Melon\Http\Response $response
     * @param enum $matchMode 匹配模式
     * 1. self::MATCH_ALL 匹配所有符合规则的路由
     * 2. self::MATCH_ONE 只匹配第一个符合规则的路由，之后都会被忽略
     */
    public function __construct( Route $route, Request $request, Response $response,  $matchMode = self::MATCH_ONE ) {
        $this->_route = $route;
        $this->_response = $response;
        $this->_matchMode = ( $matchMode === self::MATCH_ALL ? self::MATCH_ALL : self::MATCH_ONE );
        $this->_method = strtolower( $request->method() );
    }
    
    /**
     * 解释路由，如果匹配立即执行回调函数
     * 
     * @param string $method 请求方法
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    protected function _parse( $method, $rule, $callback ) {
        if( $method !== $this->_method || ! $rule ||
            ( $this->_matchMode === self::MATCH_ONE && $this->_matchTotal > 0 ) ) {
            return;
        }
        $parseInfo = array();
        $this->_route->setConfig( array(
            $method => array(
                $rule => 'lucky'
            )
        ) )->parse( $parseInfo );
        
        if( $parseInfo ) {
            $this->_matchTotal++;
            ob_start();
            call_user_func_array( $callback, $parseInfo['args'] );
            $content = ob_get_contents();
            ob_clean();
            $this->_response->send( $content );
        }
    }
    
    /**
     * 根据get方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function get( $rule, $callback ) {
        $this->_parse( 'get', $rule, $callback );
    }
    
    /**
     * 根据post方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function post( $rule, $callback ) {
        $this->_parse( 'post', $rule, $callback );
    }

    /**
     * 根据put方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function put( $rule, $callback ) {
        $this->_parse( 'put', $rule, $callback );
    }
    
    /**
     * 根据delete方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function delete( $rule, $callback ) {
        $this->_parse( 'delete', $rule, $callback );
    }

    /**
     * 根据head方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function head( $rule, $callback ) {
        $this->_parse( 'head', $rule, $callback );
    }

    /**
     * 根据patch方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function patch( $rule, $callback ) {
        $this->_parse( 'patch', $rule, $callback );
    }

    /**
     * 根据options方法匹配当前路由，如果匹配立即执行回调函数
     * 
     * @param string $rule 路由规则
     * @param callble $callback 回调函数
     * @return void
     */
    public function options( $rule, $callback ) {
        $this->_parse( 'options', $rule, $callback );
    }
    
    /**
     * 得到路由匹配总数
     * 
     * @return int
     */
    public function matchTotal() {
        return $this->_matchTotal;
    }
}