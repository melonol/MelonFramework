<?php

namespace Melon\System;

defined('IN_MELON') or die('Permission denied');

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
	 * @throw Exception\SourceException
	 */
	static public function parse( $path = '' ) {
		
	}
	
	static private function _debugBacktrace() {
		
	}

}