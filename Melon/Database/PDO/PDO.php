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
 * 基于原生PDO的扩展
 * 
 * <pre>
 * 增强了错误和调试方面的功能，程序记录了executes、exec以及query的执行次数和相关信息
 * 可以通过errorsTotal、executesTotal、statementsTotal、lastStatement获取这些信息
 * setErrorHandler可以设置一个查询错误时的处理方法
 * restoreErrorHandler还原上一个错误处理方法
 * 
 * 相对于原生PDO还改变了一些缺省设置
 * 1. 查询结果集只返回关联数组
 * 2. 查询错误时抛出一个warning警告
 * 3. statement class默认使用\Melon\Database\PDO\Statement（同样也是增强错误调试方面，建议搭配使用）
 * 
 * 其它按照PDO接口正常使用即可
 * </pre>
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 * @link http://www.php.net/manual/zh/book.pdo.php pdo官方文档
 */
class PDO extends \PDO {
    
    /**
     * 缺省设置
     * 
     * @var array
     * @link http://www.php.net/manual/zh/pdo.setattribute.php
     */
    static protected $_defaultDriverOptions = array(
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING,
        \PDO::ATTR_STATEMENT_CLASS => array( '\Melon\Database\PDO\Statement' )
    );
    
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
     * 查询错误总数
     * 
     * @var int
     */
    protected $_numErrors;
    
    /**
     * 执行prepare、exec和query的总数
     * 
     * @var int
     */
    protected $_numExecutes;
    
    /**
     * 通过prepare和query生成PDOStatements实例的总数
     * 
     * @var int
     */
    protected $_numStatements;
    
    /**
     * 最近的一个查询语句
     * 
     * @var string 
     */
    protected $_lastStatement;
    
    /**
     * 构造器
     * 
     * @param string $dsn 包含请求连接到数据库的信息
     * @param string $user [可选] DSN字符串中的用户名
     * @param string $pass [可选] DSN字符串中的密码
     * @param array $driverOptions [可选] 一个具体驱动的连接选项的键=>值数组
     * @return void
     * @link http://www.php.net/manual/zh/pdo.construct.php
     */
    public function __construct( $dsn, $user = null, $pass = null, array $driverOptions = array() ) {
        $_driverOptions = $driverOptions;
        foreach( self::$_defaultDriverOptions as $option => $value ) {
            if( ! isset( $_driverOptions[ $option ] ) ) {
                $_driverOptions[ $option ] = self::$_defaultDriverOptions[ $option ];
            }
        }
        parent::__construct( $dsn, $user, $pass, $_driverOptions );
        
        $this->_numErrors = 0;
        $this->_numExecutes = 0;
        $this->_numStatements = 0;
        $this->_lastStatement = '';
    }

    /**
     * 查询预处理
     * 
     * @param string $statement 目标数据库服务器有效的SQL语句
     * @param array $driverOptions [可选] 包含一个或多个键=>值对的PDOStatement对象对象设置属性值的数组
     * @return mixed 如果解释成功，返回一个PDOStatement实例对象，否则返回false
     * @link http://www.php.net/manual/zh/pdo.prepare.php
     */
    public function prepare( $statement, $driverOptions = array() ) {
        $this->_numStatements++;
        $this->_lastStatement = $statement;
        $result = parent::prepare( $statement, $driverOptions );
        $this->_errorHandler( $result );
        return $result;
    }

    /**
     * 执行一个常规查询语句
     * 
     * @param string $statement 目标数据库服务器有效的SQL语句
     * @return mixed 如果执行成功，返回一个PDOStatement实例对象，否则返回false
     * @link http://www.php.net/manual/zh/pdo.query.php
     */
    public function query( $statement ) {
        $this->_numExecutes++;
        $this->_numStatements++;
        $this->_lastStatement = $statement;
        $result = parent::query( $statement );
        $this->_errorHandler( $result );
        return $result;
    }

    /**
     * 执行一个影响数据表行数的语句
     * 
     * @param string $statement 目标数据库服务器有效的SQL语句
     * @return mixed 如果执行成功，返回受影响的行数，否则返回false
     * @link http://www.php.net/manual/zh/pdo.exec.php
     */
    public function exec( $statement ) {
        $this->_numExecutes++;
        $this->_lastStatement = $statement;
        $result = parent::exec( $statement );
        $this->_errorHandler( $result );
        return $result;
    }

    /**
     * 设置一个错误处理方法，当查询错误时触发
     * 
     * @param callable $callable 参数是\PDO::errorInfo中的信息，有三个
     * 1. $sqlState            string     SQLSTATE码
     * 2. $driverCode          int        普通错误码
     * 3. $driverMessage       string     普通错误信息
     * @throws \Melon\Exception\RuntimeException
     * @return void
     */
    public function setErrorHandler( $callable ) {
        if( ! is_callable( $callable ) ) {
            \Melon::throwException( 'PDO错误处理必需是一个可调用的方法' );
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
     * 设置或执行错误处理方法
     * 
     * @param flase|PDOStatement $result 一个预处理或者查询的结果
     * <pre>
     * 如果是false，则执行当前对象的错误处理方法
     * 如果是PDOStatement对象，并且含有setErrorHandler方法时，则为这个PDOStatement对象注入这个错误处理方法
     * 当PDOStatement执行发生错误时就会调用它
     * 实际上原生的PDOStatement是没有setErrorHandler方法的，你可以使用本框架提供的一个扩展：
     * \Melon\Database\PDO\Statement
     * 不过，当前PDO已经默认使用它，所以你并不需要做什么操作
     * </pre>
     * @return void
     */
    protected function _errorHandler( $result ) {
        if( $result !== false ) {
            if( is_object( $result ) && method_exists( $result, 'setErrorHandler' ) ) {
                // 再次封装errorHandler，使numErrors得以自增
                $errorHandler = $this->_errorHandler;
                $numErrors = &$this->_numErrors;
                $result->setErrorHandler( function() use( $errorHandler, &$numErrors ) {
                    $numErrors++;
                    $errorHandler && call_user_func_array( $errorHandler, func_get_args() );
                } );
            }
        } else {
            $this->_numErrors++;
            $this->_errorHandler && call_user_func_array( $this->_errorHandler, $this->errorInfo() );
        }
    }
    
    /**
     * 获取查询错误总数
     * 
     * @return int
     */
    public function errorsTotal() {
        return $this->_numErrors;
    }
    
    /**
     * 获取执行prepare、exec和query的总数
     * 
     * @return int
     */
    public function executesTotal() {
        return $this->_numExecutes;
    }
    
    /**
     * 获取通过prepare和query生成PDOStatements实例的总数
     * 
     * @return int
     */
    public function statementsTotal() {
        return $this->_numStatements;
    }
    
    /**
     * 获取最近的一个查询语句
     * 
     * @return string
     */
    public function lastStatement() {
        return $this->_lastStatement;
    }
}