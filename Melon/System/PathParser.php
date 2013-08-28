<?php

namespace Melon\System;
use Melon\Exception;

//defined('IN_MELON') or die('Permission denied');

/**
 * 路径解释器
 */
class PathParser {
	
	static private $_cache = array();
	
	private function __construct() {
		;
	}
	
	/**
	 * 解释一个路径
	 * 
	 * @param string $path
	 * @return array
	 * @throws Exception\SourceException
	 */
	static public function parse( $path = '' ) {
		// 获得backstrace列表
		$debug_backtrace = debug_backtrace();
		// 第一个backstrace就是调用import的来源脚本
		$source = $debug_backtrace[0];
		
		// 得到调用源的目录路径，和文件路径结合，就可以算出完整路径
		$source_dir = dirname( $source['file'] );
		$real_path = realpath( $source_dir . '/' . $path );
		if( ! file_exists( $real_path ) ) {
			$real_path = realpath( $path );
			if( ! file_exists( $real_path ) ||
					strpos( $real_path, realpath( '.' ) ) === false ) {
				throw new Exception\SourceException( '文件不存在' );
			}
		}
		require $real_path;
	}
	
	static private function _debugBacktrace() {
		
	}

}