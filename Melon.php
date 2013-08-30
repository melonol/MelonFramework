<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', false );

class Base {
	
	final protected function __construct() {
		;
	}

	final static public function app() {
		
	}
}
Base::app();

require 'Melon/Exception/BaseException.php';
require 'Melon/Exception/SourceException.php';
require 'Melon/System/PathTrace.php';

include System\PathTrace::parse( './Melon/Loader/BaseLoader.php' );