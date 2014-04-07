<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://git.oschina.net/397574898/MelonFramework
 * @author Melon <denglh1990@qq.com>
 * @version 0.2.0
 */

namespace Melon\Base;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 路径跟踪器
 * 
 * 使用它可以跟踪并解释某次方法调用的所在路径，或者解释一个相对于调用方法所属文件路径的真实路径（绝对路径）
 * PathTrace可以看作一个魔术类
 * 
 * 本类使用debug_backtrace函数抓取调用方法栈信息，我把类设置为最简单管理的纯静态
 * 因为debug_backtrace很容易发生变化，非常难把握，不当或过度使用将会让你的代码陷于泥潭中
 * 使用途中如果遇到一些的问题，希望你能帮助我一起去完善它
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
final class PathTrace {
	
	protected function __construct() {
		;
	}
	
	/**
	 * 解释一个文件或目录路径的真实路径。
	 * 
	 * @param string $targetPath 相对或绝对文件、目录路径
	 * 相对路径是相对于执行这个 parse 方法的文件所在目录路径来说的。
	 * 比如在 /MelonFramework/Melon.php 文件中：
	 * <pre>
	 * echo PathTrace::real( './Melon/System/PathTrace.php' );
	 * // 输出：/MelonFramework/Melon/System/PathTrace.php
	 * </pre>
	 * 
	 * @param boolean $getSource [可选] 是否获取调用者的文件路径。一般它用来做一些权限之类的验证
	 * 在 /MelonFramework/Melon.php 文件中：
	 * <pre>
	 * print_r( PathTrace::real( './Melon/System/PathTrace.php', true ) );
	 * // 输出：
	 * Array
	 * (
	 *		[source] => /MelonFramework/Melon.php
	 *		[target] => /MelonFramework/Melon/System/PathTrace.php
	 * )
	 * </pre>
	 * 
	 * @return string|array|false
	 */
	public static function real( $targetPath, $getSource = false ) {
		if( empty( $targetPath ) ) {
			return false;
		}
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
		if( $realPath !== false && $getSource ) {
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
	public static function source() {
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
	private static function _getSourceTrace( $ignoreTrace = 2 ) {
		// debug_backtrace的性能还是不错的，不过需要注意的是要开启DEBUG_BACKTRACE_IGNORE_ARGS
		// 它是PHP5.3.6才开始被支持的，正因为增加了这项特性才让我的想法得以实现
		// DEBUG_BACKTRACE_IGNORE_ARGS会忽略方法栈的参数
		// 想想，通过参数传递对象是很普遍的事情，如果是你的系统有很多大对象
		// debug_backtrace返回的信息量是多么庞大，如果多次使用，这样的内存消耗还是挺大的
		if( defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' ) ) {
			$debugBacktrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		} else {
			$debugBacktrace = debug_backtrace();
		}
		$sourceTrace = null;
		// 忽略自身，因为数组索引从0开始，所以索引也不用减1
		if( isset( $debugBacktrace[ $ignoreTrace ] ) ) {
			$sourceTrace = $debugBacktrace[ $ignoreTrace ];
			// 闭包是没有路径的，但可以在下一个栈里取
			if( $sourceTrace['function'] === '{closure}' ) {
				$sourceTrace = $debugBacktrace[ $ignoreTrace - 1 ];
			} elseif( isset( $debugBacktrace[ $ignoreTrace + 1 ] ) &&
					$debugBacktrace[ $ignoreTrace + 1 ]['function'] === 'spl_autoload_call' ) {
				$sourceTrace = $debugBacktrace[ $ignoreTrace + 1 ];
			}
		}
		// 当只有两个的时候，直接取第2个
		// 一般情况下表示在根目录下
		else if( count( $debugBacktrace ) === 2 ) {
			$sourceTrace = $debugBacktrace[1];
		}
		
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
				}
			}
			return $sourceTrace;
		}
		return false;
	}
}