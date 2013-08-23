<?php

namespace Melon;
define( 'IN_MELON', true);

class Melon {
	
	final static public function app() {
		
	}
}
DB('table')->query();
DB::query();

class DB {
	
	function __construct() {
		echo 'melon db';
	}
}
