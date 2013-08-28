<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', false );

class Melon {
	
	final static public function app() {
		
	}
}

require 'Melon/Exception/BaseException.php';
require 'Melon/Exception/SourceException.php';
require 'Melon/System/PathTrace.php';

include System\PathTrace::parse( './Melon/Loader/BaseLoader.php' );