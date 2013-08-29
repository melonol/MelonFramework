<?php

namespace Melon\System;
use Melon\Exception;

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
	public function parse( $path, $getTrace = false, array $ignoreTrace = array() ) {
		if( empty( $path ) ) {
			return false;
		}
		$strFirst = isset( $path[0] ) ? $path[0] : '';
		$strSecond = isset( $path[1] ) ? $path[1] : '';
		$isAbsolute = ( $strFirst === '/' ||
				//windows盘符
				( stripos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $strFirst ) !== false &&
					$strSecond === ':' ) );
		$realPath = $path;
		if( ! $isAbsolute ) {
			// 通过方法栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出完整路径
			$callerTrace = self::_getCallerTrace( $ignoreTrace );
			// 如果有方法使用eval，它在方法栈中的file路径可能会像这样：
			// /MelonFramework/Melon.php(21) : eval()'d code
			// 不过没关系，dirname会帮我们处理掉
			$sourceDir = dirname( $callerTrace['file'] );
			$realPath = $sourceDir . '/' . $path;
		}
		return realpath( $realPath );
	}
	
	private function _getCallerTrace( array $ignoreTrace = array() ) {
		$debugBacktrace = debug_backtrace();
		// 总是把自己忽略掉
		array_shift( $debugBacktrace );
		if( empty( $ignoreTrace ) ) {
			return ( isset( $debugBacktrace[0] ) ? $debugBacktrace[0] : false );
		}
		foreach( $debugBacktrace as $trace ) {
			$func = $trace['function'];
			if( isset( $trace['class'] ) ) {
				$method = $trace['class'] . $trace['type'] . $func;
			}
			if( ! in_array( $method, $ignoreTrace ) ) {
				return $trace;
			}
		}
		return false;
	}
}