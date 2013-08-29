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
	
	final static public function load( $file ) {
		include_once System\PathTrace::parse( $file, false, array( 'Melon\Base::load' ) );
	}
}
$s = microtime(true);
for( $i = 0; $i < 100; $i++ ) {
	Base::load( './Melon/Loader/BaseLoader.php' );
}
echo number_format( microtime(true) - $s, 3 );