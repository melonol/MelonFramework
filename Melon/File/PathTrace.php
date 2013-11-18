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
	 * <pre>
	 * echo PathTrace::parse( './Melon/System/PathTrace.php' );
	 * // 输出：/MelonFramework/System/PathTrace.php
	 * </pre>
	 * 
	 * @param boolean $getSource [可选] 是否获取调用者的文件路径。一般它用来做一些权限之类的验证
	 * 在 /MelonFramework/Melon.php 文件中：
	 * <pre>
	 * print_r( PathTrace::parse( './Melon/System/PathTrace.php', true ) );
	 * // 输出：
	 * Array
	 * (
	 *		[source] => /MelonFramework/Melon.php
	 *		[target] => /MelonFramework/Melon/System/PathTrace.php
	 * )
	 * </pre>
	 * 
	 * @param array $ignoreTrace [可选] 格式请看 self::_getSourceTrace 
	 * 如果提供这项参数，并且 $getSource 设置为 true ，
	 * 在调用栈中向上查找 source 信息的时候，将会忽略包含 $ignoreTrace 中的方法的栈
	 * 
	 * @return string|array|false
	 */
	public static function parse( $targetPath, $getSource = false ) {
		if( empty( $targetPath ) ) {
			return false;
		}
		$s = microtime(true);
		$_targetPath = $targetPath;
		// 初始化一个变量来保存调用者的栈信息
		$sourceTrace = array();
		// 第一步要做的就是要判断这是绝对路径还是相对路径，这样好分别处理
		if( ! \Melon\Base\Func\isAbsolutePath( $_targetPath ) ) {
			// 通过栈得到最近调用源的目录路径，和相对文件路径结合，就可以算出绝对路径
			$sourceTrace = self::_getSourceTrace();
			$sourceDir = dirname( $sourceTrace['file'] );
			$_targetPath = $sourceDir . DIRECTORY_SEPARATOR . $_targetPath;
		}
		
		// 路径计算完毕，我用realpath来检查有效性，顺便格式化它
		$realPath = realpath( $_targetPath );
		// 客户端可能要求获取调用者的路径
		// 如果调用者和被调用者任意一个路径不存在，统一返回假
		if( $realPath !== false && $getSource === true ) {
			$sourceTrace = ( empty( $sourceTrace ) ?
				self::_getSourceTrace() : $sourceTrace );
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
	public static function sourceFile() {
		$sourceTrace = self::_getSourceTrace();
		return empty( $sourceTrace ) ? false : $sourceTrace['file'];
	}
	
	/**
	 * 通过php提供的debug_backtrace函数中获取调用源的栈
	 * 
	 * @param int $ignoreTrace [可选] 忽略前面多少个栈，因栈已经包含了目前方法的调用信息
	 * 为了正确取得值，需要把自己调用的信息忽略掉
	 * @return array|false
	 */
	private static function _getSourceTrace( $ignoreTrace = 3 ) {
		// debug_backtrace的性能还是不错的，不过需要注意的是要开启DEBUG_BACKTRACE_IGNORE_ARGS
		// 它是PHP5.3.6才开始被支持的，正因为增加了这项特性才让我的想法得以实现
		// DEBUG_BACKTRACE_IGNORE_ARGS会忽略方法栈的参数
		// 想想，通过参数传递对象是很普遍的事情，如果是你的系统有很多大对象
		// debug_backtrace返回的信息量是多么庞大，如果多次使用，这样的内存消耗还是挺大的
		// TODO::php5.3.6以下不要使用DEBUG_BACKTRACE_IGNORE_ARGS
		$debugBacktrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$sourceTrace = $debugBacktrace[ $ignoreTrace - 1 ];
		
		if( ! empty( $sourceTrace ) ) {
			// 由于PHP提供的内部动态调用方法（比如call_user_func、invoke等）不会产生栈来源，即没有file这个值
			// 这会对我们的操作产生影响，必要时要使用反射来确保这些值存在
			if( isset( $sourceTrace['file'] ) ) {
				$sourceFile =& $sourceTrace['file'];
				// 如果有方法使用eval，它在栈中的file路径可能会像这样：
				//	/MelonFramework/Melon.php(21) : eval()'d code
				// 我用了正则表达式过滤它们,这是优雅但不太好的解决办法，正则不会很快
				// 更好的解决办法是：不要使用eval
				if ( strpos( $sourceFile, 'eval()\'d code' ) !== false ) {
					$evalExp = '/\(\d+\)\s:\seval\(\)\'d\scode/';
					$sourceFile = preg_replace( $evalExp, '', $sourceFile );
				}
			} else {
				//使用反射获取方法声明的文件和行数
				try {
					if( isset( $sourceTrace['class'] ) ) {
						$reflection = new \ReflectionMethod( $sourceTrace['class'], $sourceTrace['function'] );
					} else {
						$reflection = new \ReflectionFunction( $sourceTrace['function'] );
					}
					$sourceTrace['file'] = $reflection->getFileName();
					$sourceTrace['line'] = $reflection->getStartLine();
				} catch ( \Exception $e ) {
					$sourceTrace = false;
					//TODO::记录错误日志
				}
			}
			return $sourceTrace;
		}
		return false;
	}
}