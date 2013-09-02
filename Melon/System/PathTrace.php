<?php

namespace Melon\System;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 路径解释器
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
		$strFirst = ( isset( $path[0] ) ? $path[0] : '' );
		$strSecond = ( isset( $path[1] ) ? $path[1] : '' );
		$isAbsolute = ( $strFirst === '/' ||
			//windows盘符
			( stripos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $strFirst ) !== false &&
				$strSecond === ':' ) );
		$_path = $path;
		$loaderTrace = array();
		if( ! $isAbsolute ) {
			// 通过方法栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出完整路径
			$loaderTrace = self::_getLoaderTrace( $ignoreTrace );
			// 如果有方法使用eval，它在方法栈中的file路径可能会像这样：
			// /MelonFramework/Melon.php(21) : eval()'d code
			// 不过没关系，dirname会帮我们处理掉
			$sourceDir = dirname( $loaderTrace['file'] );
			$_path = $sourceDir . DIRECTORY_SEPARATOR . $path;
		}
		$realPath = realpath( $_path );
		if( $realPath !== false && $getLoader === true ) {
			$loaderTrace = ( empty( $loaderTrace ) ?
				self::_getLoaderTrace( $ignoreTrace ) : $loaderTrace );
			if( ! empty( $loaderTrace ) ) {
				return array(
					'loader' => $loaderTrace['file'],
					'load' => $realPath,
				);
			}
		}
		return $realPath;
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