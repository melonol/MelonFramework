<?php

/**
 * 一些杂合但很有用的函数
 * 有些东西它们可能很简单，如果没有相关类似的函数，没有必要单独写成类
 */
namespace Melon\Base\Func;

//TODO::优先获取xdebug的trace
function debugTrace() {
	
}

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