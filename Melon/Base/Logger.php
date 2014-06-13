<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Base;

use \Melon\Exception;

defined('IN_MELON') or die('Permission denied');

/**
 * 日志助手
 * 
 * 以时间命名自动分割日志文件，简化写入操作
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
class Logger {
    
    /**
     * 日志目录路径
     * @var string
     */
    protected $_dir;
    
    /**
     * 日志文件路径
     * @var string
     */
    protected $_file;
    
    /**
     * 日志前缀名
     * @var string
     */
    protected $_filePrefix;
    
    /**
     * 日志分割大小
     * @var int
     */
    protected $_splitSize;
    
    /**
     * 命名格式
     * @var string
     */
    protected $_type = 'txt';
    
    /**
     * 日志文件日期后缀
     * 
     * 当到达指定分割大小时，新的日志文件将使用第一个元素作为命名后缀
     * 如果文件增长得太快，第一个元素不够用时，将使用第二个元素作为命名后缀
     * 依次类推
     * @var array
     */
    protected $_dateSuffix = array(
        '_H', ':i', ':s'
    );
    
    /**
     * 默认使用的日期后缀
     * @var string
     */
    protected $_date = 'Y-m-d';
    
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
        if( ! is_dir( $dir ) ) {
            $mkdir = mkdir( $dir, 0777, true );
            if( ! $mkdir ) {
                throw new Exception\RuntimeException( "无法创建日志目录：{$dir}" );
            }
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
    protected function _setFile() {
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
            throw new Exception\RuntimeException( "无法写入日志：{$this->_file}" );
        }
        $date = date( 'Y-m-d H:i:s', \Melon::env( 'time' )  );
        $write = "[{$date}] {$string}\r\n";
        fwrite( $handle, $write );
        fclose( $handle );
        
        $this->_locked = false;
    }
}