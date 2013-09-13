<?php

namespace Melon\System;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 路径跟踪器
 * 
 * 使用它可以跟踪并解释某次方法调用的所在路径，或者解释一个相对于调用方法所属文件路径的真实路径（绝对路径）
 * 我把PathTrace当作一个魔术类，能根据逻辑的上下文的产生对应的结果
 * 
 * 听起来有点绕，基本上你不需要理解它，交给Melon来做就可以了
 * 本类使用debug_backtrace函数抓取调用方法栈信息，我把类设置为最简单管理的纯静态
 * 因为debug_backtrace很灵活，却非常难把握，不当或过度使用将会让你的代码陷于泥潭中
 */
class PathTrace {
	
	private function __construct() {
		;
	}
	
	/**
	 * 解释一个文件或目录路径的真实路径。
	 * 
	 * @param string $target_path 相对或绝对文件、目录路径
	 * <p>相对路径是相对于执行这个<b>parse</b>方法的文件所在目录路径来说的。
	 * 比如在<b>/MelonFramework/Melon.php</b>文件中：
	 * <code>
	 * echo PathTrace::parse( './Melon/System/PathTrace.php' );
	 * // 输出：/MelonFramework/System/PathTrace.php
	 * </code></p>
	 * 
	 * @param boolean $getSource [optional] 是否获取调用者的文件路径。一般它用来做一些权限之类的验证
	 * <p>
	 * 在<b>/MelonFramework/Melon.php</b>文件中：
	 * <code>
	 * print_r( PathTrace::parse( './Melon/System/PathTrace.php', true ) );
	 * // 输出：
	 * Array
	 * (
	 *		[source] => /MelonFramework/Melon.php
	 *		[target] => /MelonFramework/Melon/System/PathTrace.php
	 * )
	 * </code></p>
	 * 
	 * @param array $ignoreTrace [optional] 格式请看<b>self::_getSourceTrace</b>
	 * <p>如果提供这项参数，并且<i>$getSource</i>设置为<b>true</b>，
	 * 在调用栈中向上查找<b>source</b>信息的时候，将会忽略包含<i>$ignoreTrace</i>中的方法的栈</p>
	 * 
	 * @return string|array|false
	 */
	public function parse( $target_path, $getSource = false ) {
		if( empty( $target_path ) ) {
			return false;
		}
		$_target_path = $target_path;
		$ignoreTrace = array( 'Melon\System\PathTrace::parse' );
		// 初始化一个变量来保存调用者的栈信息
		$sourceTrace = array();
		// 第一步要做的就是要判断这是绝对路径还是相对路径，这样好分别处理
		if( ! self::_isAbsolutePath( $target_path ) ) {
			// 通过栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出绝对路径
			$sourceTrace = self::_getSourceTrace( $ignoreTrace );
			$sourceDir = dirname( $sourceTrace['file'] );
			$_target_path = $sourceDir . DIRECTORY_SEPARATOR . $target_path;
		}
		// 路径计算完毕，我用realpath来检查有效性，顺便格式化它
		$realPath = realpath( $_target_path );
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
	public function getSourceFile() {
		$sourceTrace = self::_getSourceTrace( array( 'Melon\System\PathTrace::getSourceFile' ) );
		return empty( $sourceTrace ) ? false : $sourceTrace['file'];
	}
	
	/**
	 * 判断一个路径是否为绝对的
	 * 
	 * @param string $path 被判断的路径
	 * @return boolean
	 */
	private function _isAbsolutePath( $path = '' ) {
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
	 * @param array $ignoreTrace [optional] 忽略哪些栈，$ignoreTrace的元素是方法名
	 * 如果是函数，则方法名为栈的function值；如果是方法，则由栈的class、type、function连接得出
	 * @return array|false
	 */
	private function _getSourceTrace( array $ignoreTrace = array() ) {
		$debugBacktrace = debug_backtrace();
		// 总是把调用自己的栈忽略掉
		array_shift( $debugBacktrace );
		
		$sourceTrace = array();
		if( empty( $ignoreTrace ) ) {
			$sourceTrace = self::_getTraceByFiltrator( $debugBacktrace, 0 );
		} else {
			foreach( $debugBacktrace as $index => $backtrace ) {
				$func = $backtrace['function'];
				if( isset( $backtrace['class'] ) ) {
					$func = $backtrace['class'] . $backtrace['type'] . $func;
				}
				if( ! in_array( $func, $ignoreTrace ) ) {
					$sourceTrace = self::_getTraceByFiltrator( $debugBacktrace, $index );
				}
			}
		}
		if( ! empty( $sourceTrace ) ) {
			$sourceFile = &$sourceTrace['file'];
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
	 * 根据索引获取某个栈
	 * 
	 * 由于PHP提供的内部调用方法（比如call_user_func）不会产生栈来源
	 * 不过会一次产生两个栈信息，其中一个是正常的，另一个是没有file值的
	 * 这会对我们的操作产生影响，我们需要一个过滤器把它过滤掉
	 * 
	 * @param array $debugBacktrace 来自debug_backtrace方法的栈
	 * @param int $index 栈索引
	 * @return array|boolean
	 */
	private function _getTraceByFiltrator( array $debugBacktrace, $index ) {
		if( ! isset( $debugBacktrace[ $index ] ) ) {
			return false;
		}
		$_index = $index;
		while( ! isset( $debugBacktrace[ $_index ]['file'] ) ) {
			if( ! isset( $debugBacktrace[ ++$_index ] ) ) {
				return false;
			}
		}
		return $debugBacktrace[ $_index ];
	}
}