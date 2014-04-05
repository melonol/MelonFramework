<?php

namespace Melon\App\Lib;

use Melon\App\Lib;

class Module {
	
	protected $_name;
	
	public function execute( $controller, $action, array $args = array() ) {
		$_controller = __NAMESPACE__ . "\\{$this->_name}\Controller\\" . ucfirst( $controller );
		$controllerObj = new $_controller();
		
		$before = $after = array();
		$ucfirstOfAction = ucfirst( $action );
		$before[ $action ] = array( $controllerObj, 'before' . $ucfirstOfAction );
		$after[ $action ] = array( $controllerObj, 'after' . $ucfirstOfAction );
		
		$controlTrigger = \Melon::trigger( $controllerObj, $before, $after );
		call_user_func( array( $controlTrigger, $action ), $args );
	}
}
