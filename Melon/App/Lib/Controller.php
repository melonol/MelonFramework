<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.2
 */

namespace Melon\App\Lib;

defined('IN_MELON') or die('Permission denied');

\Melon::load( __DIR__ . DIRECTORY_SEPARATOR . 'Func.php' );

/**
 * APP的控制器接口
 * 
 * 它提供了请求、回应和视图的功能
 */
abstract class Controller {
    
    /**
     * 当前请求实例
     * 
     * @var \Melon\Http\Request
     */
    public $request;
    
    /**
     * 回应实例
     * 
     * @var \Melon\Http\Response 
     */
    public $response;
    
    /**
     * 视图实例
     * 
     * @var \Melon\App\Lib\View 
     */
    public $view;
    
    /**
     * 语言包容器
     * 
     * @var \Melon\App\Lib\Lang
     */
    public $lang;
    
    /**
     * 跳转到指定连接
     * 
     * @param string $url
     * @param boolean $useAlink 是否使用alink，如果是则第一个参数$url为alink格式。
     * 关于alink请查看\Melon\App\Lib\Func下的alink函数
     */
    public function location( $url, $useAlink = false ) {
        $_url = ( $useAlink ? Func\alink( $url ) : $url );
        \Melon::httpResponse()->setStatus( 301 )->setHeader( 'location', $_url )->send();
    }
}