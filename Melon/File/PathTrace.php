<?php

namespace Melon\File;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 路径跟踪器
 * 
 * 使用它可以跟踪并解释某次方法调用的所在路径，或者解释一个相对于调用方法所属文件路径的真实路径（绝对路径）
 * 我把PathTrace当作一个魔术类，能根据逻辑的上下文的产生对应的结果，听起来有点绕
 * 
 * 本类使用debug_backtrace函数抓取调用方法栈信息，我把类设置为最简单管理的纯静态
 * 因为debug_backtrace很容易发生变化，非常难把握，不当或过度使用将会让你的代码陷于泥潭中
 */
final class PathTrace {
	
	private function __construct() {
		;
	}
	
	/**
	 * 解释一个文件或目录路径的真实路径。
	 * 
	 * @param string $targetPath 相对或绝对文件、目录路径
	 * 相对路径是相对于执行这个 parse 方法的文件所在目录路径来说的。
	 * 比如在 /MelonFramework/Melon.php 文件中：
	 * <code>
	 * echo PathTrace::parse( './Melon/System/PathTrace.php' );
	 * // 输出：/MelonFramework/System/PathTrace.php
	 * </code>
	 * 
	 * @param boolean $getSource [可选] 是否获取调用者的文件路径。一般它用来做一些权限之类的验证
	 * 在 /MelonFramework/Melon.php 文件中：
	 * <code>
	 * print_r( PathTrace::parse( './Melon/System/PathTrace.php', true ) );
	 * // 输出：
	 * Array
	 * (
	 *		[source] => /MelonFramework/Melon.php
	 *		[target] => /MelonFramework/Melon/System/PathTrace.php
	 * )
	 * </code>
	 * 
	 * @param array $ignoreTrace [可选] 格式请看 self::_getSourceTrace 
	 * 如果提供这项参数，并且 $getSource 设置为 true ，
	 * 在调用栈中向上查找 source 信息的时候，将会忽略包含 $ignoreTrace 中的方法的栈
	 * 
	 * @return string|array|false
	 */
	public static function parse( $targetPath, $getSource = false, array $ignoreTrace = array() ) {
		if( empty( $targetPath ) ) {
			return false;
		}
		$_targetPath = $targetPath;
		$ignoreTrace = array( __NAMESPACE__ . '\PathTrace::parse' );
		// 初始化一个变量来保存调用者的栈信息
		$sourceTrace = array();
		// 第一步要做的就是要判断这是绝对路径还是相对路径，这样好分别处理
		if( ! self::_isAbsolutePath( $_targetPath ) ) {
			// 通过栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出绝对路径
			$sourceTrace = self::_getSourceTrace( $ignoreTrace );
			$sourceDir = dirname( $sourceTrace['file'] );
			$_targetPath = $sourceDir . DIRECTORY_SEPARATOR . $_targetPath;
		}
		// 路径计算完毕，我用realpath来检查有效性，顺便格式化它
		$realPath = realpath( $_targetPath );
		// 客户端可能要求获取调用者的路径
		// 如果调用者和被调用者任意一个路径不存在，统一返回假
		if( $realPath !== false && $getSource === true ) {
			$sourceTrace = ( empty( $sourceTrace ) ?
				self::_getSourceTrace( $ignoreTrace ) : $sourceTrace );
			if( empty( $sourceTrace ) ) {
				return false;
			}
			return array(
				'source' => $sourceTrace['file'],
				'target' => $realPath,
			);
		}
		return $realPath;
	}
	
	/**
	 * 获取调用自己的方法的所在文件
	 * 
	 * @return string|false
	 */
	public static function getSourceFile() {
		$sourceTrace = self::_getSourceTrace( array( __NAMESPACE__ . '\PathTrace::getSourceFile' ) );
		return empty( $sourceTrace ) ? false : $sourceTrace['file'];
	}
	
	/**
	 * 判断一个路径是否为绝对的
	 * 
	 * @param string $path 被判断的路径
	 * @return boolean
	 */
	private static function _isAbsolutePath( $path = '' ) {
		// 主流的系统我见过有两种绝对路径：
		//	一种是以/号开头的，而另一种是字母和:号开头（猜猜看它们可能是什么系统？\偷笑）
		// 如果你还见过其它的形式，或者有更好的判断绝对路径的方法，请告诉我
		$strFirst = ( isset( $path[0] ) ? $path[0] : '' );
		$strSecond = ( isset( $path[1] ) ? $path[1] : '' );
		$isAbsolute = ( $strFirst === '/' ||
			( stripos( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', $strFirst ) !== false &&
				$strSecond === ':' ) );
		return $isAbsolute;
	}
	
	/**
	 * 通过php提供的debug_backtrace函数中获取调用源的栈
	 * 
	 * 为了方便处理，它总是把调用自己的栈忽略掉
	 * 
	 * @param array $ignoreTrace [可选] 忽略哪些栈，$ignoreTrace的元素是方法名
	 * 如果是函数，则方法名为栈的function值；如果是方法，则由栈的class、type、function连接得出
	 * @return array|false
	 */
	private static function _getSourceTrace( array $ignoreTrace = array() ) {
		$debugBacktrace = debug_backtrace();
		// 总是把调用自己的栈忽略掉
		array_shift( $debugBacktrace );
		
		$sourceTrace = array();
		if( empty( $ignoreTrace ) ) {
			$sourceTrace = self::_getCompleteTrace( $debugBacktrace, 0 );
		} else {
			foreach( $debugBacktrace as $index => $backtrace ) {
				$func = $backtrace['function'];
				if( isset( $backtrace['class'] ) ) {
					$func = $backtrace['class'] . $backtrace['type'] . $func;
				}
				if( ! in_array( $func, $ignoreTrace ) ) {
					$sourceTrace = self::_getCompleteTrace( $debugBacktrace, $index );
					break;
				}
			}
		}
		if( ! empty( $sourceTrace ) ) {
			$sourceFile =& $sourceTrace['file'];
			// 如果有方法使用eval，它在栈中的file路径可能会像这样：
			//	/MelonFramework/Melon.php(21) : eval()'d code
			// 我用了正则表达式过滤它们,这是优雅但不太好的解决办法，正则不会很快
			// 更好的解决办法是：不要使用eval
			if ( strpos( $sourceFile, 'eval()\'d code' ) !== false ) {
				$evalExp = '/\(\d+\)\s:\seval\(\)\'d\scode/';
				$sourceFile = preg_replace( $evalExp, '', $sourceFile );
			}
			return $sourceTrace;
		}
		return false;
	}
	
	/**
	 * 根据索引获取某个包含相对完整信息的栈
	 * 
	 * 由于PHP提供的内部动态调用方法（比如call_user_func、invoke等）不会产生栈来源，即没有file这个值
	 * 这会对我们的操作产生影响，必要时要使用反射来确保这些值存在
	 * 
	 * 为什么需要索引这个参数，难道不能直接使用对应索引的方法栈？
	 * 我只是做一个预留，可能我想多了，方法栈实在有点变化莫测
	 * 给定索引值的方式，如果以后发现一些特殊问题，可以根据索引值向上寻找一些可能有用的信息来修正
	 * 
	 * @param array $debugBacktrace 来自debug_backtrace方法的栈
	 * @param int $index 栈索引
	 * @return array|false
	 */
	private static function _getCompleteTrace( array $debugBacktrace, $index ) {
		$trace = false;
		if( isset( $debugBacktrace[ $index ] ) ) {
			$trace =& $debugBacktrace[ $index ];
			if( isset( $trace['file'] ) ) {
				return $trace;
			}
			//使用反射获取方法声明的文件和行数
			try {
				if( isset( $trace['class'] ) ) {
					$reflection = new \ReflectionMethod( $trace['class'], $trace['function'] );
				} else {
					$reflection = new \ReflectionFunction( $trace['function'] );
				}
				$trace['file'] = $reflection->getFileName();
				$trace['line'] = $reflection->getStartLine();
			} catch ( \Exception $e ) {
				$trace = false;
				//TODO::记录错误日志
			}
		}
		return $trace;
	}
}