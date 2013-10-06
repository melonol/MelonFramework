<?php
define('IN_MELON', true);
define('MELON_ROOT', '/www/Melon/MelonFramework/');

require_once MELON_ROOT . '/Melon/Helper/Set.php';
require_once MELON_ROOT . '/Melon/File/LoaderSet.php';
use Melon\File\LoaderSet;

class LoaderSetTest extends PHPUnit_Framework_TestCase {
	
	private $_array;
	
	public function setUp() {
		$this->_array = new LoaderSet();
	}
	
	public function testSet() {
		$this->_array->set( 'k', 'v' );
		$this->assertEquals( $this->_array->get( 'k' ), 'v' );
		
		$this->_array->set( 'k1', 0 );
		$this->assertEquals( $this->_array->get( 'k1' ), 0 );
		
		$this->_array->set( 'k2', null );
		$this->assertEquals( $this->_array->get( 'k2' ), null );
		
		$this->_array->set( 'k3', false );
		$this->assertEquals( $this->_array->get( 'k3' ), false );
	}
	
	public function testGet() {
		$this->testSet();
	}
	
	/**
	 * 测试替换模式
	 */
	public function testSetMode() {
		$this->_array = new LoaderSet( array(),LoaderSet::REPLACE_ABSOLUTE );
		$this->_array->set( 'k', 'v' );
		$this->_array->set( 'k', null );
		$this->assertEquals( $this->_array->get( 'k' ), null );
		
		$this->_array = new LoaderSet( array(),LoaderSet::REPLACE_NOT );
		$this->_array->set( 'k', 'v' );
		$this->_array->set( 'k', 'v1' );
		$this->_array->set( 'k', null );
		$this->assertEquals( $this->_array->get( 'k' ), 'v' );
		
		$this->_array = new LoaderSet( array(),LoaderSet::REPLACE_RELAXED );
		$this->_array->set( 'k', 'v' );
		$this->_array->set( 'k', 'v1' );
		$this->_array->set( 'k', null );
		$this->assertEquals( $this->_array->get( 'k' ), 'v1' );
		
	}
	
	
	public function testRemove() {
		$this->_array->set( 'k', 'v' );
		$this->assertEquals( $this->_array->get( 'k' ), 'v' );
		$this->_array->remove( 'k' );
		$this->assertEquals( $this->_array->get( 'k' ), null );
	}
	
	public function testHas() {
		$items = array(
			'k1' => 'v1',
			'k2' => false,
			'k3' => 0
		);
		$this->_array->setItems( $items );
		$this->assertTrue( $this->_array->has( 'k1' ) );
		$this->assertTrue( $this->_array->has( 'k2' ) );
		$this->assertTrue( $this->_array->has( 'k3' ) );
		$this->assertFalse( $this->_array->has( 'k5' ) );
		$this->assertFalse( $this->_array->has( null ) );
		$this->assertFalse( $this->_array->has( 0 ) );
	}
	
	public function testCount() {
		$items = range( 1, 100 );
		$this->_array->setItems( $items );
		$this->assertEquals( count( $items ), count( $this->_array ) );
	}
	
	public function testClear() {
		$items = range( 1, 100 );
		$this->_array->setItems( $items );
		$this->_array->clear();
		$this->assertEquals( count( $this->_array ), 0 );
	}
	
	/**
	 * 测试迭代器
	 */
	public function testIterator() {
		$items = array(
			'k1' => 'v1',
			'k2' => 'v2',
			'k3' => 'v3',
		);
		$this->_array->setItems( $items );
		foreach( $items as $key => $value ) {
			$this->assertTrue( in_array( $value, $items ) );
		}
	}
	
	public function testArrayAccess() {
		$this->_array['k1'] = 'v1';
		$this->_array['k2'] = 0;
		$this->_array['k3'] = false;
		$this->assertEquals( $this->_array->get( 'k1' ), 'v1' );
		$this->assertEquals( $this->_array->get( 'k2' ), 0 );
		$this->assertEquals( $this->_array->get( 'k3' ), false );
		$this->assertTrue( isset( $this->_array['k1'] ) );
		$this->assertTrue( isset( $this->_array['k2'] ) );
		$this->assertFalse( isset( $this->_array['k4'] ) );
	}
	
	public function testAttrAccess() {
		$this->_array->k1 = 'v1';
		$this->_array->k2 = 0;
		$this->_array->k3 = false;
		$this->assertEquals( $this->_array->get( 'k1' ), 'v1' );
		$this->assertEquals( $this->_array->get( 'k2' ), 0 );
		$this->assertEquals( $this->_array->get( 'k3' ), false );
	}
}