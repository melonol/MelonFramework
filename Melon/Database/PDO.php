<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://git.oschina.net/397574898/MelonFramework
 * @author Melon <denglh1990@qq.com>
 * @version 0.1.0
 */

namespace Melon\Database;

use Melon\Exception;
defined('IN_MELON') or die('Permission denied');

class PDO extends \PDO {
	
	static protected $_defaultDriverOptions = array(
		\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING,
		\PDO::ATTR_STATEMENT_CLASS => array( 'PDOStatemento' )
	);
	
	protected $_lastErrorHandler;

	protected $_errorHandler;

	protected $_numErrors;
	
	protected $_numExecutes;
	
	protected $_numStatements;
	
	protected $_lastStatement;

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

	public function prepare( $statement, array $driverOptions = array() ) {
		$this->_numStatements++;
		$this->_lastStatement = $statement;
		$result = parent::prepare( $statement, $driverOptions );
		$this->_errorHandler( $result );
		return $result;
	}

	public function query( $statement ) {
		$this->_numExecutes++;
		$this->_numStatements++;
		$this->_lastStatement = $statement;
		$result = parent::query( $statement );
		$this->_errorHandler( $result );
		return $result;
	}

	public function exec( $statement ) {
		$this->_numExecutes++;
		$this->_lastStatement = $statement;
		$result = parent::exec( $statement );
		$this->_errorHandler( $result );
		return $result;
	}

	public function setErrorHandler( $callable ) {
		if( ! is_callable( $callable ) ) {
			throw new Exception( 'PDO错误处理必需是一个可调用的方法' );
		}
		$this->_lastErrorHandler = $this->_errorHandler;
		$this->_errorHandler = $callable;
	}
	
	public function restoreErrorHandler() {
		$this->_errorHandler = $this->_lastErrorHandler;
	}
	
	private function _errorHandler( $result ) {
		if( $result !== false ) {
			if( is_object( $result ) && method_exists( $result, 'setErrorHandler' ) ) {
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
	
	public function numErrors() {
		return $this->_numErrors;
	}
	
	public function numExecutes() {
		return $this->_numExecutes;
	}
	
	public function numStatements() {
		return $this->_numStatements;
	}
	
	public function lastStatement() {
		return $this->_lastStatement;
	}
}