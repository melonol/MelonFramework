<?php

namespace Melon\System;
use Melon\Exception;

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
	 * 解释一个路径
	 * 
	 * @param string $path
	 * @return string|array|false
	 */
	public function parse( $path, $getLoader = false, array $ignoreTrace = array() ) {
		if( empty( $path ) ) {
			return false;
		}
		$_path = $path;
		// 初始化一个变量来保存调用者的栈信息
		$loaderTrace = array();
		// 第一步要做的就是要判断这是绝对路径还是相对路径，这样好分别处理
		$isAbsolute = self::_isAbsolutePath( $path );
		if( ! $isAbsolute ) {
			// 通过栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出绝对路径
			$loaderTrace = self::_getLoaderTrace( $ignoreTrace );
			// 如果有方法使用eval，它在栈中的file路径可能会像这样：
			//	/MelonFramework/Melon.php(21) : eval()'d code
			// 不过没关系，dirname会帮我们处理掉特殊的部分
			$sourceDir = dirname( $loaderTrace['file'] );
			$_path = $sourceDir . DIRECTORY_SEPARATOR . $path;
		}
		// 路径计算完毕，我用realpath来检查有效性，顺便格式化它
		$realPath = realpath( $_path );
		// 客户端可能要求获取调用者的路径
		// 如果调用者和被调用者任意一个路径不存在，统一返回假
		if( $realPath !== false && $getLoader === true ) {
			$loaderTrace = ( empty( $loaderTrace ) ?
				self::_getLoaderTrace( $ignoreTrace ) : $loaderTrace );
			if( ! empty( $loaderTrace ) ) {
				$loaderFile = $loaderTrace['file'];
				// 处理eval调用本方法时，file出现的特殊字符
				// 因为要保留文件名，我用了正则表达式过滤它们
				// 这是优雅但不太好的解决办法，正则不会很快
				// 更好的解决办法是：不要使用eval
				if ( strpos( $loaderFile, 'eval()\'d code' ) !== false ) {
					$evalExp = '/\(\d+\)\s:\seval\(\)\'d\scode/';
					$loaderFile = preg_replace( $evalExp, '', $loaderFile );
				}
				return array(
					'loader' => $loaderTrace['file'],
					'load' => $realPath,
				);
			}
			return false;
		}
		return $realPath;
	}
	
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
	
	private function _getLoaderTrace( array $ignoreTrace = array() ) {
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