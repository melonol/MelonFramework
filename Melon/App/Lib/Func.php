<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\App\Lib\Func;
use Melon\App\Lib\App;

defined('IN_MELON') or die('Permission denied');

/**
 * 根据路由配置(type值)自动处理连接，简单称呼为auto link。
 * 你只需要提供符合路由规则的连接即可产生完整的URL
 * 
 * 举个例子，alink( '/Index/Index' );
 * 当type为requestKey，requestKey为p时，得到的值大概是这样
 * http://domain.com/index.php?p=/Index/Index
 * 
 * 当type为incompletePathinfo时，得到的值大概是这样
 * http://domain.com/index.php/Index/Index
 * 
 * 当type为completePathinfo时，得到的值大概是这样
 * http://domain.com/Index/Index
 * 
 * 而http://domain.com 则是当前请求的连接脚本，如果你不想使用，可以使用第二个参数关闭它
 * 
 * @param string $link 连接
 * @param string $complete 补完连接，使其完整，它会在连接前加上当前的HTTP协议和域名
 * @return string
 */
function alink( $link = '', $complete = true ) {
    $url = '';
    $routeType = App::env( 'routeConfig.type' );
    if( $complete ) {
        $http = $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $url = $http . $_SERVER['HTTP_HOST'];
    }
    if( $routeType === 'incompletePathinfo' ) {
        $url .= $_SERVER['SCRIPT_NAME'] . '/' . ltrim( $link, '/' );
    } elseif( $routeType === 'completePathinfo' ) {
        $dir = str_replace( DIRECTORY_SEPARATOR, '/', dirname( $_SERVER['SCRIPT_NAME'] ) );
        $url .= ( $dir === '/' ? '' : $dir ) . '/' . ltrim( $link, '/' );
    } else {
        $key = App::env( 'routeConfig.requestKey' );
        if( ! $key ) {
            App::throwException( '参数requestKey为空，无法正常跳转。请确认路由配置中配置了requestKey' );
        }
        $url .= $_SERVER['SCRIPT_NAME'] . '?' . $key . '=' . str_replace( '?', '&', $link );
    }
    return $url;
}