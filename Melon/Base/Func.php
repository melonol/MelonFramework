<?php

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
	//	一种是以/号开头的，而另一种是字母和:号开头（猜猜看它们可能是什么系统？\偷笑）
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
 *	'l1' => array(
 *		'l2' => array(
 *			'l3' => array(
 *				'k' => 'v'
 *			)
 *		)
 *	)
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