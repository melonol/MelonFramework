<?php

namespace Melon\Util;

defined( 'IN_MELON' ) or die( 'Permission denied' );

class RecursiveSet extends Set {
	
	protected static $_id_count = 0;
	
	protected static $_lock_id = false;
	
	protected $_id;
	
	public function __construct( array $items = array(), $replaceMode = self::REPLACE_ABSOLUTE ) {
		if( self::$_lock_id ) {
			$this->_setId( self::$_lock_id );
		} else {
			$this->_setId( ++self::$_id_count );
		}
		$this->_replaceMode = $replaceMode;
		$this->_recursiveSet( $items );
	}
	
	protected function _getCongenericInstance( array $items = array() ) {
		self::$_lock_id = $this->getId();
		$instance = new self( $items, $this->_replaceMode );
		self::$_lock_id = false;
		return $instance;
	}
	
	protected function _setId( $id ) {
		$this->_id = (int)$id;
	}
	
	protected function _recursiveSet( array $items ) {
		foreach( $items as $key => $value ) {
			$this->set( $key, $value );
		}
	}
	
	public function set( $key, $value ) {
		if( is_array( $value ) ) {
			$this->_array[ $key ] = $this->_getCongenericInstance( $value, $this->_replaceMode );
		} else {
			$this->_array[ $key ] = $value;
		}
	}
	
	public function getProto( $set = null ) {
		$array = array();
		foreach( $this->getItems() as $key => $value ) {
			if( $value instanceof self ) {
				$array[ $key ] = $this->getProto( $value );
			} else {
				$array[ $key ] = $value;
			}
		}
	}
	
	public function getReplaceMode() {
		return $this->_replaceMode;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function __valueOf() {
		return $this->getId();
	}
	
	public function __toString() {
		return $this->getId() . '';
	}
}