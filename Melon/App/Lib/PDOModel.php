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
 * APP模型
 */
class PDOModel extends \Melon\Database\PDO\Model {
    
    /**
     * 表名
     */
    protected $_table;

    public function __construct() {
        $pdo = App::db();
        if( ! $pdo instanceof \PDO ) {
            self::throwException( '请提供有效的PDO驱动实例' );
        }
        parent::__construct( App::table( $this->_table ), $pdo );
    }
}