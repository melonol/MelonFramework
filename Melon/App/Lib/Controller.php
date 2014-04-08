<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://git.oschina.net/397574898/MelonFramework
 * @author Melon <denglh1990@qq.com>
 * @version 0.2.0
 */

namespace Melon\App\Lib;

defined('IN_MELON') or die('Permission denied');

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

}