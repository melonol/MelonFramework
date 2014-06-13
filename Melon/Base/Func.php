<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

/**
 * 一些杂合但很有用的函数
 * 有些东西它们可能很简单，如果没有相关类似的函数，没有必要单独写成类
 */
namespace Melon\Base\Func;

/**
 * 判断一个路径是否为绝对的
 * 
 * @param string $path 被判断的路径
 * @return boolean
 */
function isAbsolutePath( $path = '' ) {
    // 主流的系统我见过有两种绝对路径：
    //    一种是以/号开头的，而另一种是字母和:号开头（猜猜看它们可能是什么系统？\偷笑）
    // 还有就是phar
    // 如果你还见过其它的形式，或者有更好的判断绝对路径的方法，请告诉我
    $strFirst = ( isset( $path[0] ) ? $path[0] : '' );
    $strSecond = ( isset( $path[1] ) ? $path[1] : '' );
    $isAbsolute = ( $strFirst === '/' ||
        ( stripos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $strFirst ) !== false && $strSecond === ':' ) ||
        ( stripos( $path, 'PHAR://' ) === 0 ) );
    return $isAbsolute;
}

/**
 * 根据键名获取数组的某个值
 * 
 * <pre>
 * 函数简化了常规的多维数组获取某个值的方式：
 * $arr = array(
 *    'l1' => array(
 *        'l2' => array(
 *            'l3' => array(
 *                'k' => 'v'
 *            )
 *        )
 *    )
 * );
 * echo getValue( $arr, 'l1.l2.l3.k' ); // v
 * </pre>
 * 
 * @param array $arr 数组
 * @param string $key 键名，键名可以由多个$delimiter隔开，表示要获取的值的层级关系
 * @param string $delimiter [可选] 分隔符
 * @return mixed 当元素不存在时返回null
 */
function getValue( array & $arr , $key, $delimiter = '.' ) {
    if( strpos( $key, $delimiter ) ) {
        $value = null;
        $level = 0;
        foreach( explode( $delimiter, $key ) as $segmentKey ) {
            if( $level > 0 && is_array( $value ) && isset( $value[ $segmentKey ] ) ) {
                $value =& $value[ $segmentKey ];
                $level++;
            } elseif( $level === 0 && isset( $arr[ $segmentKey ] ) ) {
                $value =& $arr[ $segmentKey ];
                $level++;
            } else {
                return null;
            }
        }
        return $value;
    }
    return ( isset( $arr[ $key ] ) ? $arr[ $key ] : null );
}

/**
 * 替换字符串里指定格式的变量
 * 
 * 字符串的变量指定为 ${变量名} 这样的格式。一个简单的使用例子：
 * echo varReplace( 'name', 'Melon', '我的名字是${name}' );
 * // 输出：我的名字是Melon
 * 
 * @param string|array $search 要搜索的变量，如果要搜索多个变量，则参数为数组
 * @param string|array $replace 替换值，如果要替换为多个值，则参数为数组
 * @param string $input 输入字符串
 * @return string 替换后的字符串
 */
function varReplace( $search, $replace, $input ) {
    $searchs = ( is_array( $search ) ? $search : array( $search ) );
    $replaces = ( is_array( $replace ) ? $replace : array( $replace ) );
    foreach( $searchs as &$value ) {
        $value = '${' . $value . '}';
    }
    return str_replace( $searchs, $replaces, $input );
}