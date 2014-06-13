<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Database\PDO;

use Melon\Exception;
defined('IN_MELON') or die('Permission denied');

/**
 * 基于原生PDOStatement的扩展
 * 
 * <pre>
 * 增强了错误处理的功能，这是为了搭配\Melon\Database\PDO\PDO而编写的
 * setErrorHandler可以设置一个查询错误时的处理方法
 * restoreErrorHandler还原上一个错误处理方法
 * </pre>
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 * @link http://www.php.net/manual/zh/book.pdo.php pdo官方文档
 */
class Statement extends \PDOStatement {
    
    /**
     * 上一个错误处理方法
     * 
     * @var callabe 
     */
    protected $_lastErrorHandler;

    /**
     * 当前错误处理方法
     * 
     * @var callabe 
     */
    protected $_errorHandler;

    /**
     * 设置一个错误处理方法，当查询错误时触发
     * 
     * @param callable $callable 参数是\PDOStatement::errorInfo中的信息，有三个
     * 1. $sqlState            string     SQLSTATE码
     * 2. $driverCode          int        普通错误码
     * 3. $driverMessage       string     普通错误信息
     * @throws \Melon\Exception\RuntimeException
     * @return void
     */
    public function setErrorHandler( $callable ) {
        if( ! is_callable( $callable ) ) {
            \Melon::throwException( 'PDOStatement错误处理必需是一个可调用的方法' );
        }
        $this->_lastErrorHandler = $this->_errorHandler;
        $this->_errorHandler = $callable;
    }
    
    /**
     * 还原上一个错误处理方法
     * 
     * 如果没有上一个，则置空
     * 
     * @return void
     */
    public function restoreErrorHandler() {
        $this->_errorHandler = $this->_lastErrorHandler;
    }
    
    /**
     * 执行一条预处理语句
     * 
     * @param array $input_parameters [可选] 一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组。所有的值作为 PDO::PARAM_STR 对待
     * 详情请参考官方文档
     * @return boolean
     * @link http://www.php.net/manual/zh/pdostatement.execute.php
     */
    public function execute( $input_parameters = array() ) {
        $result = parent::execute( $input_parameters );
        if( $result === false && $this->_errorHandler ) {
            call_user_func_array( $this->_errorHandler, $this->errorInfo() );
        }
        return $result;
    }
}