<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\App\Lib;

defined('IN_MELON') or die('Permission denied');

App::load( __DIR__ . DIRECTORY_SEPARATOR . 'Func.php' );

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
     * 构造函数（废话）
     */
    public function __construct() {
        
        // 为控制器设置属性
        $this->request = App::httpRequest();
        $this->response = App::httpResponse();
        
        $module = App::module( App::env( "moduleName" ) );
        // 兼容 <=0.2.2
        if( method_exists( $module, 'getCommentLang' ) ) {
            $this->lang = $module->getCommentLang();
        } else {
            $this->lang = $module->lang();
        }
        
        App::load( __DIR__ . DIRECTORY_SEPARATOR . 'Func.php' );
        $this->view = new View( $this );
        // 注入alink标签
        $this->view->assignTag( 'alink', array(
            'callable' => '\Melon\App\Lib\Func\alink',
            'args' => array(
                'ln' => '',
                'comp' => true,
            )
        ) );
    }
    
    /**
     * 跳转到指定连接
     * 
     * @param string $url
     * @param boolean $useAlink 是否使用alink，如果是则第一个参数$url为alink格式。
     * 关于alink请查看\Melon\App\Lib\Func下的alink函数
     */
    public function location( $url, $useAlink = false ) {
        $_url = ( $useAlink ? Func\alink( $url ) : $url );
        App::httpResponse()->setStatus( 301 )->setHeader( 'location', $_url )->send();
        App::halt();
    }
    
    /**
     * 使用alink跳转到指定连接
     * 
     * @param string $alink
     */
    public function alocation( $alink ) {
        $this->location( $alink, true );
    }
}