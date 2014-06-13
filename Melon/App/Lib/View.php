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

use Melon\Util;
use \Melon\Http;

defined('IN_MELON') or die('Permission denied');

/**
 * APP视图
 * 
 * 视图使用\Melon\Util\Template的基本方法进行组合
 */
class View {
    
    /**
     * 视图对象
     * 
     * @var \Melon\Util\Template 
     */
    private $_view;
    
    /**
     * 控制器对象
     * 
     * @var Object
     */
    private $_controller;

    public function __construct( $controller ) {
        $this->_controller = $controller;
        
        // 视图设置好基本的目录，方便管理和使用
        $this->_view = App::template();
        $this->_view->setTemplateDir( App::env( 'appDir' ) . DIRECTORY_SEPARATOR . 'Module' .
            DIRECTORY_SEPARATOR . App::env( 'config.privatePre' ) . App::env( 'moduleName' ) . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . App::env( 'controller' ) );
        $this->_view->setCompileDir( App::env( 'appDir' ) . DIRECTORY_SEPARATOR .
            'Data' . DIRECTORY_SEPARATOR . 'TplCache' );
    }
    
    /**
     * 注入一个变量
     * 
     * @param string $key 变量名
     * @param mixed $value 值
     * @return \Melon\App\Lib\View
     */
    public function assign( $key, $value ) {
        $this->_view->assign( $key, $value );
        return $this;
    }
    
    /**
     * 注入一组变量
     * 
     * @param array $vars 变量组，每个元素都表示一个变量
     * @return \Melon\App\Lib\View
     */
    public function assignItem( array $vars ) {
        $this->_view->assignItem( $vars );
        return $this;
    }
    
    /**
     * 注入一个自定义标签
     * 
     * @param string $tagname 自定义标签名
     * @param array $setting 标签设置
     * 要提供的参数：
     * 1. callable        string    可直接调用的函数的名称
     * 2. args        array    参数数组，key是参数名称，value是默认值，数组元素必须按照callable函数的参数顺序一一对应
     * @return \Melon\App\Lib\View
     */
    public function assignTag( $tagname, $setting ) {
        $this->_view->assignTag( $tagname, $setting );
        return $this;
    }
    
    /**
     * 注入一组自定义标签
     * 
     * @param array $tags 标签组，每个元素都表示一个自定义标签
     * @return \Melon\App\Lib\View
     */
    public function assignTagItem( array $tags ) {
        $this->_view->assignTagItem( $tags );
        return $this;
    }
    
    
    /**
     * 把模板运行结果返回
     * 
     * @param string $template 模板路径，如果设置了模板目录，则它是相对于模板目录下的文件路径
     * @return string
     */
    public function fetch( $template ) {
        return $this->_view->fetch( $template );
    }
    
    /**
     * 把模板运行结果输出
     * 
     * @param string $template 模板路径，如果设置了模板目录，则它是相对于模板目录下的文件路径
     * @return void
     */
    public function display( $template ) {
        // 注入语言包
        if( isset( $this->_controller->lang ) && ( is_array( $this->_controller->lang ) || $this->_controller->lang instanceof Util\Set ) ) {
            $this->_view->assign( 'lang', $this->_controller->lang );
        }
        // 注入请求参数
        if( isset( $this->_controller->request ) && $this->_controller->request instanceof Http\Request) {
            $this->_view->assignItem( $this->_controller->request->inputs() );
        }
        // 注入环境变量
        $this->_view->assign( 'env', App::env() );
        
        $this->_view->display( $template );
    }
    
}