<?php

define('IN_MELON', true);
define('MELON_TEST', true);
define('MELON_ROOT', '/www/Melon/MelonFramework/');
require_once MELON_ROOT . '/Melon.php';

class MelonTest extends PHPUnit_Framework_TestCase {
	
	private $_testDir;

	public function setup() {
		$this->_testDir = __DIR__ . '/MelonTest';
	}
	
	public function testLoad() {
		Melon::load( $this->_testDir . '/load.php' );
		$this->assertTrue( function_exists( 'loadTest' ) );
		// 测试权限
		try {
			Melon::load( $this->_testDir . '/_Package/load.php' );
			$this->assertTrue( false );
		} catch( \Exception $e ) {
			if( ! $e instanceof \Melon\Exception\BaseException ) {
				throw $e;
			}
			$this->assertTrue( true );
		}
	}
	
	public function testAcquire() {
		$this->assertEquals( Melon::acquire( $this->_testDir . '/acquire.php' ), 'testTrue' );
		// 测试权限
		try {
			Melon::acquire( $this->_testDir . '/_Package/load.php' );
			$this->assertTrue( false );
		} catch( \Exception $e ) {
			if( ! $e instanceof \Melon\Exception\BaseException ) {
				throw $e;
			}
			$this->assertTrue( true );
		}
	}
}