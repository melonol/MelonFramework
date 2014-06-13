<?php

namespace __APPNAME__\Module;

use Melon\App\Lib;

class __MODULENAME__ extends \Melon\App\Lib\Module {
    
    protected function _controller( $controller ) {
        if( file_exists( __DIR__ . DIRECTORY_SEPARATOR . '__PRIVATE_PRE____MODULENAME__' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controller . '.php' ) ) {
            $controllerName = __NAMESPACE__ . '\__PRIVATE_PRE____MODULENAME__\Controller\\' . $controller;
            return new $controllerName();
        }
        return false;
    }
    
    public function lang() {
        $data = \__APPNAME__::acquire( __DIR__ . DIRECTORY_SEPARATOR . '__PRIVATE_PRE____MODULENAME__' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . \__APPNAME__::env( 'config.lang' ) . '.php' );
        return new Lib\Lang( $data );
    }
}