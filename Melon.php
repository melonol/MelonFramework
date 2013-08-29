<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', true );
define( 'DS', DIRECTORY_SEPARATOR );

const ROOT = __DIR__;

require ROOT . '/Melon/Exception/BaseException.php';
require ROOT . '/Melon/Exception/SourceException.php';
require ROOT . '/Melon/System/PathTrace.php';

class Base {
	
	final protected function __construct() {
		;
	}

	final static public function load( $file ) {
		include_once System\PathTrace::parse( $file, false, array( 'Melon\Base::load' ) );
	}
}
