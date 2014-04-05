<?php

namespace Melon\App\Lib;

use Melon\App\Lib;

abstract class Module {
	
	protected $_name;
	
	public function execute( $controller, $action, array $args = array() ) {
		$controllerObj = $this->getController( $controller );
		
		$before = $after = array();
		$ucfirstOfAction = ucfirst( $action );
		if( method_exists( $controllerObj, 'before' . $ucfirstOfAction ) ) {
			$before[ $action ] = array( $controllerObj, 'before' . $ucfirstOfAction );
		}
		if( method_exists( $controllerObj, 'after' . $ucfirstOfAction ) ) {
			$after[ $action ] = array( $controllerObj, 'after' . $ucfirstOfAction );
		}
		$controlTrigger = \Melon::trigger( $controllerObj, $before, $after );
		call_user_func( array( $controlTrigger, $action ), $args );
	}
	
	abstract public function getController( $controller );
}
