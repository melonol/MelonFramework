<?php

namespace Melon\Base;

defined('IN_MELON') or die('Permission denied');


class Log {
	
	private $_dir;
	
	private $_file;
	
	private $_type = 'txt';
	
	private $_filePrefix;
	
	private $_splitSize;
	
	private $_dateSuffix = array(
		'_H', ':i', ':s'
	);
	
	private $_date = 'Y-m-d';
	
	private $_locked = false;
	
	public function __construct( $dir, $filePrefix, $splitSize = 10 ) {
		if( ! is_readable( $dir ) ) {
			throw new \Melon\Exception\RuntimeException( "日志目录不可访问：{$this->_file}" );
		}
		$this->_dir = $dir;
		$this->_splitSize = $split * 1024 * 1024;
		$this->_filePrefix = $filePrefix;
		$this->_setFile();
	}
	
	private function _setFile() {
		$dateSuffix = date( $this->_date, \Melon::env( 'time' ) );
		$file = $this->_filePrefix . '-' . $dateSuffix . '.' . $this->_type;
		$this->_file = $this->_dir . DIRECTORY_SEPARATOR . $file;
	}
	
	public function write( $string ) {
		if( $this->_locked ) {
			return;
		}
		$this->_locked = true;
		
		while( file_exists( $this->_file ) && ( filesize( $this->_dir ) >= $this->_splitSize ) ) {
			$this->_date .= array_shift( $this->_dateSuffix );
			$this->_setFile();
		}
		$handle = @fopen( $this->_file, 'a' );
		if( ! $handle ) {
			throw new \Melon\Exception\RuntimeException( "无法写入日志：{$this->_file}" );
		}
		$date = date( 'Y-m-d H:i:s', \Melon::env( 'time' )  );
		$write = "[{$date}] $string\r\n";
		fwrite( $handle, $write );
		fclose( $handle );
		
		$this->_locked = false;
	}
}