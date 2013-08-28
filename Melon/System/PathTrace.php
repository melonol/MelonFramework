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
	public function parse( $path = '', $get_trace = false, array $ignore_trace = array() ) {
		// 获得backstrace列表
		$debug_backtrace = debug_backtrace();
		// 第一个backstrace就是调用import的来源脚本
		$source = $debug_backtrace[0];
		
		$str_first = isset( $path[0] ) ? $path[0] : '';
		$str_second = isset( $path[1] ) ? $path[1] : '';
		$is_absolute = ( $str_first === '/' ||
				//windows盘符
				( stripos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $str_first ) !== false &&
					$str_second === ':' ) );
		$real_path = $path;
		if( ! $is_absolute ) {
			// 得到调用源的目录路径，和文件路径结合，就可以算出完整路径
			$source_dir = dirname( $source['file'] );
			$real_path = $source_dir . '/' . $path;
		}
		return realpath( $real_path );
	}
	
	private function _debugBacktrace( array $ignore_trace = array() ) {
		
	}
}