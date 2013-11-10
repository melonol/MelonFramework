<?php

namespace Melon\Base;

defined('IN_MELON') or die('Permission denied');


class Logger {
	
	/**
	 * 日志目录路径
	 * @var string
	 */
	private $_dir;
	
	/**
	 * 日志文件路径
	 * @var string
	 */
	private $_file;
	
	/**
	 * 日志前缀名
	 * @var string
	 */
	private $_filePrefix;
	
	/**
	 * 日志分割大小
	 * @var int
	 */
	private $_splitSize;
	
	/**
	 * 命名格式
	 * @var string
	 */
	private $_type = 'txt';
	
	/**
	 * 日志文件日期后缀
	 * 
	 * 当到达指定分割大小时，新的日志文件将使用第一个元素作为命名后缀
	 * 如果文件增长得太快，第一个元素不够用时，将使用第二个元素作为命名后缀
	 * 依次类推
	 * @var array
	 */
	private $_dateSuffix = array(
		'_H', ':i', ':s'
	);
	
	/**
	 * 默认使用的日期后缀
	 * @var string
	 */
	private $_date = 'Y-m-d';
	
	/**
	 * 是否锁定写入动作
	 * 
	 * 不是锁定写入文件，是锁定写入的动作
	 * 可能你会认为PHP单线程不可能同一时间会发生执行两次同一个方法
	 * 不过如果写入日志的时候发生错误并被捕捉，捕捉程序再执行日志写入
	 * 日志再发生错误，这样造成一个无限的递归
	 * 所以我加入了简单的锁来避免这个问题
	 * @var boolean
	 */
	private $_locked = false;
	
	/**
	 * 构造函数
	 * 
	 * @param string $dir 日志存放目录
	 * @param string [可选] $filePrefix 日志前缀
	 * @param string [可选] $splitSize 自动分割大小，单位M，当为0时不进行分割
	 * @throws \Melon\Exception\RuntimeException
	 */
	public function __construct( $dir, $filePrefix = 'log', $splitSize = 10 ) {
		//TODO::自动创建日志目录
		if( ! is_readable( $dir ) ) {
			throw new \Melon\Exception\RuntimeException( "日志目录不可访问：{$dir}" );
		}
		$this->_dir = $dir;
		$this->_splitSize = $splitSize * 1024 * 1024;
		$this->_filePrefix = $filePrefix;
		$this->_setFile();
	}
	
	/**
	 * 设置文件路径
	 * 
	 * 根据时间来设置
	 * @return void
	 */
	private function _setFile() {
		$dateSuffix = date( $this->_date, \Melon::env( 'time' ) );
		$file = $this->_filePrefix . '-' . $dateSuffix . '.' . $this->_type;
		$this->_file = $this->_dir . DIRECTORY_SEPARATOR . $file;
	}
	
	/**
	 * 写入内容
	 * 
	 * @param string $string 日志内容，内容以追加模式写入日志，并且会自动在内容结尾加上换行符
	 * @return void
	 * @throws \Melon\Exception\RuntimeException
	 */
	public function write( $string ) {
		if( $this->_locked ) {
			return;
		}
		$this->_locked = true;
		
		if( $this->_splitSize > 0 ) {
			while( file_exists( $this->_file ) && ( filesize( $this->_file ) >= $this->_splitSize ) &&
					! empty( $this->_dateSuffix ) ) {
				$this->_date .= array_shift( $this->_dateSuffix );
				$this->_setFile();
			}
		}
		$handle = @fopen( $this->_file, 'a' );
		if( ! $handle ) {
			throw new \Melon\Exception\RuntimeException( "无法写入日志：{$this->_file}" );
		}
		$date = date( 'Y-m-d H:i:s', \Melon::env( 'time' )  );
		$write = "[{$date}] {$string}\r\n";
		fwrite( $handle, $write );
		fclose( $handle );
		
		$this->_locked = false;
	}
}