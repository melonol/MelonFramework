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

use Melon\Util;
use Melon\Base\Func;

defined('IN_MELON') or die('Permission denied');

/**
 * 语言容器，用于设置和获取语言包
 */
class Lang extends Util\Set {
    
    /**
     * 获取某个语言字段，并替换其中的变量
     * 
     * @param string $key 语言键名
     * @param array $replaces 替换变量，格式：array( 变量 => 替换值 ... )
     * @return string
     */
    public function replace( $key, array $replaces ) {
        $value = parent::get( $key );
        if( ! empty( $replaces ) && $value ) {
            return Func\varReplace( array_keys( $replaces ), $replaces, $value );
        }
        return $value;
    }
}
