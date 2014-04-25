<?php

namespace __APPNAME__\Module;

class __MODULENAME__ extends \Melon\App\Lib\Module {
    
    public function getController( $controller ) {
        if( file_exists( __DIR__ . DIRECTORY_SEPARATOR . '__PRIVATE_PRE____MODULENAME__' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controller . '.php' ) ) {
            $controllerName = __NAMESPACE__ . '\__PRIVATE_PRE____MODULENAME__\Controller\\' . $controller;
            return new $controllerName();
        }
        return false;
    }
    
    public function getCommentLang() {
        return \__APPNAME__::acquire( __DIR__ . DIRECTORY_SEPARATOR . '__PRIVATE_PRE____MODULENAME__' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . 'Comment.php' );
    }
}