<?php

namespace Melon\System;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 路径解释器
 * 我认为这能够有效降低系统逻辑的耦合性
 * PathTrace是整个框架的核心部分，而事实上它真的很好用
 */
class PathTrace {
	
	public function __construct() {
		;
	}
	
	/**
	 * 解释一个文件或目录路径的真实路径。
	 * 
	 * @param string $target_path 相对或绝对文件、目录路径
	 * <p>相对路径是相对于执行这个<b>parse</b>方法的文件所在目录路径来说的。
	 * 比如在<b>/MelonFramework/Melon.php</b>文件中：
	 * <code>
	 * echo PathTrace::parse( './Melon/System/PathTrace.php' );
	 * // 输出：/MelonFramework/System/PathTrace.php
	 * </code></p>
	 * 
	 * @param boolean $getSource [optional] 是否获取调用者的文件路径。一般它用来做一些权限之类的验证
	 * <p>
	 * 在<b>/MelonFramework/Melon.php</b>文件中：
	 * <code>
	 * print_r( PathTrace::parse( './Melon/System/PathTrace.php', true ) );
	 * // 输出：
	 * Array
	 * (
	 *		[source] => /MelonFramework/Melon.php
	 *		[target] => /MelonFramework/Melon/System/PathTrace.php
	 * )
	 * </code></p>
	 * 
	 * @param array $ignoreTrace [optional] 格式请看<b>self::_getSourceTrace</b>
	 * <p>如果提供这项参数，并且<i>$getSource</i>设置为<b>true</b>，
	 * 在调用栈中向上查找<b>source</b>信息的时候，将会忽略包含<i>$ignoreTrace</i>中的方法的栈</p>
	 * 
	 * @return string|array|false
	 */
	public function parse( $target_path, $getSource = false, array $ignoreTrace = array() ) {
		if( empty( $target_path ) ) {
			return false;
		}
		$_target_path = $target_path;
		// 初始化一个变量来保存调用者的栈信息
		$sourceTrace = array();
		// 第一步要做的就是要判断这是绝对路径还是相对路径，这样好分别处理
		$isAbsolute = self::_isAbsolutePath( $target_path );
		if( ! $isAbsolute ) {
			// 通过栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出绝对路径
			$sourceTrace = self::_getSourceTrace( $ignoreTrace );
			// 如果有方法使用eval，它在栈中的file路径可能会像这样：
			//	/MelonFramework/Melon.php(21) : eval()'d code
			// 不过没关系，dirname会帮我们处理掉特殊的部分
			$sourceDir = dirname( $sourceTrace['file'] );
			$_target_path = $sourceDir . DIRECTORY_SEPARATOR . $target_path;
		}
		// 路径计算完毕，我用realpath来检查有效性，顺便格式化它
		$realPath = realpath( $_target_path );
		// 客户端可能要求获取调用者的路径
		// 如果调用者和被调用者任意一个路径不存在，统一返回假
		if( $realPath !== false && $getSource === true ) {
			$sourceTrace = ( empty( $sourceTrace ) ?
				self::_getSourceTrace( $ignoreTrace ) : $sourceTrace );
			if( ! empty( $sourceTrace ) ) {
				$sourceFile = $sourceTrace['file'];
				// 处理eval调用本方法时，file出现的特殊字符
				// 因为要保留文件名，我用了正则表达式过滤它们
				// 这是优雅但不太好的解决办法，正则不会很快
				// 更好的解决办法是：不要使用eval
				if ( strpos( $sourceFile, 'eval()\'d code' ) !== false ) {
					$evalExp = '/\(\d+\)\s:\seval\(\)\'d\scode/';
					$sourceFile = preg_replace( $evalExp, '', $sourceFile );
				}
				return array(
					'source' => $sourceTrace['file'],
					'target' => $realPath,
				);
			}
			return false;
		}
		return $realPath;
	}
	
	/**
	 * 
	 * @param type $path
	 * @return type
	 */
	private function _isAbsolutePath( $path = '' ) {
		// 主流的系统我见过有两种绝对路径：
		//	一种是以/号开头的，而另一种是字母和:号开头（猜猜看它们可能是什么系统？\偷笑）
		// 如果你还见过其它的形式，或者有更好的判断绝对路径的方法，请告诉我
		$strFirst = ( isset( $path[0] ) ? $path[0] : '' );
		$strSecond = ( isset( $path[1] ) ? $path[1] : '' );
		$isAbsolute = ( $strFirst === '/' ||
			( stripos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $strFirst ) !== false &&
				$strSecond === ':' ) );
		return $isAbsolute;
	}
	
	private function _getSourceTrace( array $ignoreTrace = array() ) {
		$debugBacktrace = debug_backtrace();
		// 总是把调用自己的栈忽略掉
		array_shift( $debugBacktrace );
		if( empty( $ignoreTrace ) ) {
			return self::_getTraceByFiltrator( $debugBacktrace, 0 );
		}
		foreach( $debugBacktrace as $index => $backtrace ) {
			$func = $backtrace['function'];
			if( isset( $backtrace['class'] ) ) {
				$func = $backtrace['class'] . $backtrace['type'] . $func;
			}
			if( ! in_array( $func, $ignoreTrace ) ) {
				return self::_getTraceByFiltrator( $debugBacktrace, $index );
			}
		}
		return false;
	}
	
	private function _getTraceByFiltrator( array $debugBacktrace, $index ) {
		if( ! isset( $debugBacktrace[ $index ] ) ) {
			return false;
		}
		$_index = $index;
		while( ! isset( $debugBacktrace[ $_index ]['file'] ) ) {
			if( ! isset( $debugBacktrace[ ++$_index ] ) ) {
				return false;
			}
		}
		return $debugBacktrace[ $_index ];
	}
}