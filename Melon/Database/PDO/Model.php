<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://git.oschina.net/397574898/MelonFramework
 * @author Melon <denglh1990@qq.com>
 * @version 0.1.0
 */

namespace Melon\Database\PDO;

use Melon\Exception;
defined('IN_MELON') or die('Permission denied');

class Model {
	
	protected $_table;

	protected $_pdo = array();

	public function __construct( $table, \PDO $pdo ) {
		$this->_table = $pdo->quote( $table );
		$this->_pdo = $pdo;
	}
	
	public function debug() {
		
	}
	
	public function fetch( $where = '', array $bindParams = array() ) {
		$sql = 'SELECT * FROM `' . $this->_table . '`';
		if( $where ) {
			$sql .= ' WHERE ' . $where;
		}
		$statement = $this->_pdo->prepare( $sql );
		if( $statement->execute( $bindParams ) ) {
			return $statement->fetch();
		}
		return false;
	}
	
	public function fetchAll( $field = '*', $where = '', array $bindParams = array(), $limit = null ) {
		$sql = 'SELECT ' . $field . ' FROM `' . $this->_table . '`';
		if( $where ) {
			$sql .= ' WHERE ' . $where;
		}
		if( $limit ) {
			$sql .= ' LIMIT ' . $limit;
		}
		$statement = $this->_pdo->prepare( $sql );
		if( $statement->execute( $bindParams ) ) {
			return $statement->fetchAll();
		}
		return false;
	}
	
	public function fetchPage( $field = '*', $page = 1, $length = 10, $where = '', array $bindParams = array() ) {
		
	}
	
	public function count( $where = '', array $bindParams = array() ) {
		
	}
	
	public function update( $where = '', array $bindParams = array() ) {
		
	}
	
	public function delete( $where = '', array $bindParams = array() ) {
		
	}
	
	public function insert( array $data = array() ) {
		
	}
}