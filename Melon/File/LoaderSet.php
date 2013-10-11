<?php

namespace Melon\File;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 保存加载信息的类
 */
class LoaderSet extends \Melon\Helper\Set {
	
	/**
	 * 使用md5格式化键名
	 * 
	 * @param mixed $key
	 * @return string 
	 */
	protected function _normalizeKey( $key ) {
		return md5( strtolower( $key ) );
	}
}