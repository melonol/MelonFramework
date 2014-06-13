<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\App\Lib;

defined('IN_MELON') or die('Permission denied');

/**
 * APP基础主体类
 */
class App extends \Melon {
    
    /**
     * 获得一个模块实例，如果模块不存在则抛出异常
     * 
     * @staticvar array $modules
     * @param string $name 模块名字
     * @return \Melon\App\Lib\class
     */
    static public function module( $name ) {
        static $modules = array();
        if( ! isset( $modules[ $name ] ) ) {
            $class = '\\' . self::env( 'appName' ) . '\Module\\' . $name;
            $ready = true;
            if( ! class_exists( $class ) ) {
                try {
                    spl_autoload( $class );
                } catch (Exception $ex) {
                    $ready = false;
                }
            }
            if( ! $ready ) {
                self::throwException( "module {$name}不存在" );
            }
            $modules[ $name ] = new $class();
        }
        return $modules[ $name ];
    }
    
    /**
     * 获取一个模块的语言包
     * 
     * @staticvar array $moduleLangs
     * @staticvar array $appLangs
     * @param string $moduleName [可选] 模块名字，如果为null，则返回公共语言包
     * @return \Melon\App\Lib\Lang
     */
    static public function lang( $moduleName = null ) {
        static $moduleLangs = array();
        static $appLangs = array();
        
        if( is_null( $moduleName ) ) {
            if( ! $appLangs ) {
                $data = self::acquire( self::env( 'appName' ) . DIRECTORY_SEPARATOR .
                    'Lang' . DIRECTORY_SEPARATOR . self::env( 'config.lang' ) . '.php' );
                $appLangs = new Lang( $data );
            }
            return $appLangs;
        } else {
            if( ! isset( $moduleLangs[ $moduleName ] ) ) {
                $moduleLangs[ $moduleName ] = self::module( $moduleName )->lang();
            }
            return $moduleLangs[ $moduleName ];
        }
    }
}
