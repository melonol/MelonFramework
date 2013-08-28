<?php
namespace Melon;
use Melon\System;

define( 'IN_MELON', false );

class Melon {
	
	final static public function app() {
		
	}
}

require './Melon/Exception/BaseException.php';
require './Melon/Exception/SourceException.php';
require './Melon/System/PathParser.php';


System\PathParser::parse( 'D:\apmserv\www\htdocs\7725\p7725svr\read.txt' );