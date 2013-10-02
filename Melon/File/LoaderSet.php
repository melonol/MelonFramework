<?php
/**
 * 
 */

namespace Melon\File;

defined( 'IN_MELON' ) or die( 'Permission denied' );

class LoaderSet extends \Melon\Helper\Set {
	
	/**
	 * 使用md5格式化键名
	 * 
	 * @param mixed $key
	 * @return string 
	 */
	protected function _normalizeKey( $key ) {
		return md5( $key );
	}
}