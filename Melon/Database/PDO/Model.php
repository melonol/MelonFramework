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
 * 基于PDO的数据表模型，它非常简单，但可以通过继承来扩展
 * 
 * 只封装了常用的增删改查操作，并带有调试功能
 * 通过openDebug和closeDebug来开启或关闭，开启的情况下会在客户端输出相关的调试信息
 * 
 * 对于字段名不作注入过滤处理，因为很多数据库可使用方法处理字段，对这些不限制了
 * 你需要自己注意这个问题
 * 
 * <pre>
 * 你可以使用以下方法对一个表进行操作：
 * fetch        查询一条结果
 * fetchAll     查询所有结果集
 * fetchPage    以分页的形式查询结果集
 * count        统计结果集行数
 * update       更新数据
 * delete       删除数据
 * insert       插入数据
 * 
 * 对update和detele方法的危险操作进行了禁止
 * 
 * 在实例过程中，需要提供一个PDO对象作用查询接口
 * 所以要方便实例，基本上你都需要自定义一个工厂方法来生产这些数据模型，我认为这是必需的
 * </pre>
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 */
class Model {
    
    /**
     * 数据表名，可带数据库前缀
     * 
     * @var string 
     */
    protected $_table;

    /**
     * PDO实例对象
     * 
     * @var \PDO 
     */
    protected $_pdo;
    
    /**
     * 调试开关标记
     * 
     * @var boolean 
     */
    protected $_debug;

    /**
     * 构造器
     * 
     * @param string $table 数据表名，可带数据库前缀
     * @param \PDO $pdo PDO实例对象
     * @return void
     */
    public function __construct( $table, \PDO $pdo ) {
        $this->_setTable( $table );
        $this->_pdo = $pdo;
        $this->_debug = false;
    }
    
    /**
     * 打开调试
     * 
     * <pre>
     * 打开后，执行查询方法时，会在客户端或日志中（注意要在框架配置中打开这些选项）看到类似这样的信息：
     * Model: Moel
     * Table: `table`
     * Method: fetch
     * Sql: SELECT * FROM `table` WHERE `id`=:id
     * Bind Parameters: Array
     * (
     *    [:id] => 1
     * )
     * </pre>
     * 
     * @return void
     */
    public function openDebug() {
        $this->_debug = true;
    }
    
    /**
     * 关闭调试
     * 
     * @return void
     */
    public function closeDebug() {
        $this->_debug = false;
    }
    
    /**
     * 记录一次调试信息
     * 
     * @param string $method 方法名，一般情况下建议使用 __FUNCTION__ 维护性更好
     * @param string $sql 相关SQL语句
     * @param array $bindParams [可选] 预处理绑定的参数
     */
    protected function _debug( $method, $sql, array $bindParams = array() ) {
        if( $this->_debug ) {
            $data = array(
                'Model' => __CLASS__,
                'Table' => $this->_table,
                'Method' => $method,
                'Sql' => $sql,
                'Bind Parameters' => print_r( $bindParams, true ),
            );
            $message = "\r\n";
            foreach( $data as $key => $value ) {
                $message .= "{$key}: {$value}\r\n";
            }
            \Melon::debugWithTrace( $message );
        }
    }
    
    /**
     * 触发一个错误
     * 
     * 根据pdo的\PDO::ATTR_ERRMODE配置，如果是\PDO::ERRMODE_EXCEPTION，则抛出异常
     * 否则只抛出一个warning警告
     * 
     * @param string $message 错误消息
     * @throws \Melon\Exception\RuntimeException
     * @return void
     */
    protected function _error( $message ) {
        if( $this->_pdo->getAttribute( \PDO::ATTR_ERRMODE ) === \PDO::ERRMODE_EXCEPTION ) {
            \Melon::throwException( $message );
        } else {
            trigger_error( $message, E_USER_WARNING );
        }
    }

    /**
     * 设置数据表名
     * 
     * 如果数据表名不带数据库前缀或不包含`，则使用`号括起来
     * 
     * @param string $table 数据表名，表名只能包含英文字母 数字 ` 以及 . 这些字符，否则抛出一个异常
     * @throws \Melon\Exception\RuntimeException
     * @return void
     */
    protected function _setTable( $table ) {
        if( ! preg_match( '/^[\w\`\.]+$/', $table ) ) {
            \Melon::throwException( "数据库表名{$table}不合法" );
        }
        if( strpos( $table, '`' ) === false && strpos( $table, '.' ) === false ) {
            $this->_table = '`' . $table . '`';
        } else {
            $this->_table = $table;
        }
    }

    /**
     * 设置where语句
     * 
     * @param string|array $where 当为字符串时直接返回；数组则键名作为字段名，元素作为值，使用=号关联，并且每组元素用AND条件拼接起来
     * @return type
     */
    protected function _setWhere( $where ) {
        if( is_array( $where ) ) {
            $_where = array();
            foreach( $where as $field => $value ) {
                $_where[] = '`' . $field . '`=' . $this->_pdo->quote( $value );
            }
            return implode( ' AND ', $_where );
        }
        return $where;
    }
    
    /**
     * 执行一个查询语句，并返回查询结果
     * 
     * @param string $sql 查询语句
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed
     */
    public function query( $sql, array $bindParams = array() ) {
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->fetchAll();
        }
        return false;
    }
    
    /**
     * 执行一个会影响结果行数的语句，并返回最后新增数据的ID
     * 
     * @param string $sql 查询语句
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed
     */
    public function exec( $sql, array $bindParams = array() ) {
        $this->_debug( __FUNCTION__, $sql );
        if( $this->_pdo->exec( $sql ) !== false ) {
            return $this->_pdo->lastInsertId();
        }
        return false;
    }

    /**
     * 查询一行结果
     * 
     * @param string|array $where 条件语句，可添加预处理标记
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed 查询成功返回相应结果集的数组，失败返回false
     */
    public function fetch( $where = '', array $bindParams = array() ) {
        $sql = 'SELECT * FROM ' . $this->_table;
        if( $where ) {
            $sql .= ' WHERE ' . $this->_setWhere( $where );
        }
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->fetch();
        }
        return false;
    }
    
    /**
     * 查询所有结果集
     * 
     * @param string $field [可选] 结果集包含的字段
     * @param string|array $where [可选] 条件语句，可添加预处理标记
     * @param array $bindParams [可选] 预处理绑定参数
     * @param string $limit [可选] 结果集范围
     * @return mixed 查询成功返回相应结果集的数组，失败返回false
     */
    public function fetchAll( $field = '*', $where = '', array $bindParams = array(), $limit = null ) {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->_table;
        if( $where ) {
            $sql .= ' WHERE ' . $this->_setWhere( $where );
        }
        if( $limit ) {
            $sql .= ' LIMIT ' . $limit;
        }
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->fetchAll();
        }
        return false;
    }
    
    /**
     * 使用分页的形式来查询结果集
     * 
     * @param string $field [可选] 结果集包含的字段
     * @param int $page [可选] 页数
     * @param int $length [可选] 行数
     * @param string|array $where [可选] 条件语句，可添加预处理标记
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed 查询成功返回相应结果集的数组，失败返回false
     */
    public function fetchPage( $field = '*', $page = 1, $length = 10, $where = '', array $bindParams = array() ) {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->_table;
        if( $where ) {
            $sql .= ' WHERE ' . $this->_setWhere( $where );
        }
        $_page = intval($page);
        $_page = ( $_page <= 0  ? 1 : $_page );
        $_length = intval($length);
        $_length = ( $_length <= 0  ? 10 : $_length );
        $offset = ( $_page - 1 ) * $length;
        $sql .= ' LIMIT ' . $offset . ', ' . $length;
        
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->fetchAll();
        }
        return false;
    }
    
    /**
     * 统计一个结果集的总数
     * 
     * @param string|array $where [可选] 条件语句，可添加预处理标记
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed 查询成功返回相应结果集的总数，失败返回false
     */
    public function count( $where = '', array $bindParams = array() ) {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_table;
        if( $where ) {
            $sql .= ' WHERE ' . $this->_setWhere( $where );
        }
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->fetchColumn();
        }
        return false;
    }
    
    /**
     * 更新数据
     * 
     * 对where条件为空或者通配操作进行了禁止
     * 
     * @param array $data 一个字段名=>值的数组
     * @param string|array $where 条件语句，可添加预处理标记
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed 更新成功返回的受影响结果的行数，失败返回false
     */
    public function update( array $data = array(), $where, array $bindParams = array() ) {
        if( ! $data ) {
            $this->_error( '更新数据不应该为空' );
            return false;
        }
        if( ! $where || trim( $where, ' ;' ) === '1' ) {
            $this->_error( '更新数据的条件不应该为空或者通配，这是非常危险的' );
            return false;
        }
        $updata = array();
        foreach( $data as $field => $value ) {
            $updata[] = '`' . $field . '`=' . $this->_pdo->quote( $value );
        }
        $sql = 'UPDATE ' . $this->_table . ' SET ' . implode( ',', $updata );
        $sql .= ' WHERE ' . strval( $this->_setWhere( $where ) );
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->rowCount();
        }
        return false;
    }
    
    /**
     * 插入一行数据
     * 
     * @param array $data 一个字段名=>值格式的数组
     * @return mixed 插入成功返回新增结果的ID，失败返回false
     */
    public function insert( array $data = array() ) {
        if( ! $data ) {
            $this->_error( '插入数据不应该为空' );
            return false;
        }
        $fields = array();
        $values = array();
        foreach( $data as $field => $value ) {
            $fields[] = '`' . $field . '`';
            $values[] = $this->_pdo->quote( $value );
        }
        $sql = 'INSERT INTO ' . $this->_table . ' (' . implode( ',', $fields ) . ') VALUES(' . implode( ',', $values ) . ')';
        $this->_debug( __FUNCTION__, $sql );
        if( $this->_pdo->exec( $sql ) !== false ) {
            return $this->_pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * 删除数据
     * 
     * 对where条件为空或者通配操作进行了禁止
     * 
     * @param string|array $where 条件语句，可添加预处理标记
     * @param array $bindParams [可选] 预处理绑定参数
     * @return mixed 删除成功返回的受影响结果的行数，失败返回false
     */
    public function delete( $where = '', array $bindParams = array() ) {
        if( ! $where || trim( $where, ' ;' ) === '1' ) {
            $this->_error( '删除数据的条件不应该为空或者通配，这是非常危险的' );
            return false;
        }
        $sql = 'DELETE FROM ' . $this->_table;
        $sql .= ' WHERE ' . strval( $this->_setWhere( $where ) );
        $this->_debug( __FUNCTION__, $sql, $bindParams );
        $statement = $this->_pdo->prepare( $sql );
        if( $statement->execute( $bindParams ) ) {
            return $statement->rowCount();
        }
        return false;
    }
}