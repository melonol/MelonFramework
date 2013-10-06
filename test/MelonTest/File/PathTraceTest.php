<?php
define('IN_MELON', true);
define('MELON_ROOT', '/www/Melon/MelonFramework/');

require_once MELON_ROOT . '/Melon/File/PathTrace.php';

class PathTraceTest extends PHPUnit_Framework_TestCase {
	
	private $_realpath;
	
	public function setUp() {
		$this->_realpath = realpath( __DIR__ . '/PathTraceTest.php' );
	}
	
	/**
	 * 测试路径
	 * parse方法使用debug_backtrace进行调用，使用一些特殊的backtrace数据测试比较有效
	 * 比如call_user_func、eval
	 */
	public function testParse() {
		$file = basename( $this->_realpath );
		$method_str = '\Melon\File\PathTrace::parse';
		
		//期望正确
		$this->assertEquals(
			\Melon\File\PathTrace::parse( $file ),
			$this->_realpath
		);
		$this->assertEquals(
			call_user_func( $method_str, $file ),
			$this->_realpath
		);
		$this->assertEquals(
			call_user_func_array( $method_str, array( $file ) ),
			$this->_realpath
		);
		$this->assertEquals(
			eval( "return $method_str( '$file' );" ),
			$this->_realpath
		);
		$this->assertEquals(
			eval( "return call_user_func( '$method_str', '$file' );" ),
			$this->_realpath
		);
		$this->assertEquals(
			eval( "return call_user_func_array( '$method_str', array('$file') );" ),
			$this->_realpath
		);
		
		//期望失败
		//随便拿几个文件和目录
		$dir = scandir( MELON_ROOT . '/Melon' );
		foreach( $dir as $file ) {
			if( $file == '.' || $file == '..' ) {
				continue;
			}
			$this->assertFalse(
				\Melon\File\PathTrace::parse( $file ), $file
			);
			error_log( call_user_func( $method_str, $file ), 3, 'err.txt' );
			$this->assertFalse(
				call_user_func( $method_str, $file ), $file
			);
			$this->assertFalse(
				call_user_func_array( $method_str, array( $file ) ), $file
			);
			$this->assertFalse(
				eval( "return $method_str( '$file' );" ), $file
			);
			$this->assertFalse(
				eval( "return call_user_func( '$method_str', '$file' );" ), $file
			);
			$this->assertFalse(
				eval( "return call_user_func_array( '$method_str', array( '$file' ) );" ), $file
			);
		}
	}
}
