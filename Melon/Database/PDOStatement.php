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

class PDOStatement extends \PDOStatement {
	
	protected $_lastErrorHandler;

	protected $_errorHandler;

	public function setErrorHandler( $callable ) {
		if( ! is_callable( $callable ) ) {
			throw new Exception( 'PDOStatement错误处理必需是一个可调用的方法' );
		}
		$this->_lastErrorHandler = $this->_errorHandler;
		$this->_errorHandler = $callable;
	}
	
	public function restoreErrorHandler() {
		$this->_errorHandler = $this->_lastErrorHandler;
	}
	
	public function execute( array $input_parameters = array() ) {
		$result = parent::execute( $input_parameters );
		if( $result === false && $this->_errorHandler ) {
			call_user_func_array( $this->_errorHandler, $this->errorInfo() );
		}
		return $result;
	}
}