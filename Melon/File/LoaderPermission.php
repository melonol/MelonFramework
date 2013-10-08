<?php
/**
 * 
 */

namespace Melon\File;
use \Melon\Exception;

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 判断加载脚本或者文件的权限
 * 
 * 程序给出一组包含路径，当加载的目标文件路径存在这组包含路径中的时候，就会要求检查权限
 * 当目标路径名称含有某个特定的前缀时，它属于同级目录下的特定脚本文件私有的
 * 除了这些脚本文件外，其它人没有权限去读取它们
 */
class LoaderPermission {
	
	/**
	 * 包含路径
	 * @var array
	 */
	protected $_includePath = array();
	
	/**
	 * 权限前缀标识符
	 * @var string
	 */
	protected $_privatePre;
	
	/**
	 * 构造函数
	 * 
	 * @param array $includePath 包含路径数组，如果'目标路径'存在包含路径中，则'载入者路径'会被检查文件读取权限
	 * 即是说包含路径是一组被管辖的范围
	 * @param string $privatePre 私有权限的前缀标识符
	 */
	public function __construct( array $includePath, $privatePre = '_' ) {
		$this->_setIncludePath( $includePath );
		$this->_privatePre = $privatePre;
	}
	
	/**
	 * 设置包含路径
	 * 
	 * @param array $includePath 包含路径数组，每个路径必需是有效的，否则会被抛出异常
	 * @throws Exception\RuntimeException
	 * @return void
	 */
	protected function _setIncludePath( array $includePath ) {
		foreach( $includePath as $path ) {
			$realPath = realpath( $path );
			if( $realPath === false ) {
				throw new Exception\RuntimeException( "{$path}不是一个有效的路径" );
			}
			$this->_includePath[] = $realPath;
		}
	}
	
	/**
	 * 判断目标文件路径是否在包含路径内
	 * 
	 * @param string $target 目标文件路径
	 * @return boolean
	 */
	protected function _inRange( $target ) {
		foreach( $this->_includePath as $path ) {
			if( strpos( $target, $path ) === 0  ) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 判断一个脚本文件是否有载入另一个文件的权限
	 * 
	 * 我把它们分别叫做'载入者路径'和'目标路径'，当载入者路径满足以下条件时，才有权限载入目标路径
	 * 1. 目标路径不在检查范围内，即不在包含路径中
	 * 2. 目标路径文件和父目录都不属于私有的
	 * 3. 某个父目录属于私有，但是载入者也在这个私有目录或者其子目录下
	 * 4. 载入者文件名与目标路径的当前父目录同级，载入者文件名（不含.php）加上私有前缀与当前父目录相等，比如 File.php和_File
	 * 
	 * 另外载入者路径和目标路径都必需是有效的，否则会被抛出异常
	 * 
	 * @param string $source 载入者路径
	 * @param string $target 目标路径
	 * @return boolean
	 * @throws Exception\RuntimeException
	 */
	public function verify( $source, $target ) {
		$_source = realpath( $source );
		$_target = realpath( $target );
		if( ! $_source || ! $_target ) {
			$errorFile = ( $_source ? $target : $source );
			throw new Exception\RuntimeException( "{$errorFile}不是一个有效的文件" );
		}
		// 准备开始检查权限，我设定如果满足要求，就立刻让程序返回
		// 可能违背了结构化编程原则，但如果要在这里遵守它，多层的if嵌套会让我头晕
		// 我喜欢遵守规则，但不喜欢看上去混乱的东西
		
		// 不在检查范围内？
		if( ! $this->_inRange( $_target ) ) {
			return true;
		}
		// 没有私有文件或者目录？
		$noPrivate = ( strpos( $_target, DIRECTORY_SEPARATOR . $this->_privatePre ) === false );
		if( $noPrivate ) {
			return true;
		}
		// 同级目录？
		// 我要加上一个目录分隔符做结尾，防止因为包含片段名称（比如'dir'和'directory'）可能导致的一些问题
		$sourceDir = dirname( $_source ) . DIRECTORY_SEPARATOR;
		$targetDir = dirname( $_target ) . DIRECTORY_SEPARATOR;
		if( $sourceDir === $targetDir ) {
			return true;
		}
		// 再确定一下是否是私有文件
		if( strpos( basename( $_target ), $this->_privatePre ) !== 0 ) {
			// 如果载入者路径包含了目标路径，则说明载入者在目标路径更里的目录
			// 这样当然是有权限的
			$includeTarget = ( strpos( $sourceDir, $targetDir ) === 0 );
			if( $includeTarget ) {
				return true;
			}
			// 反过来，只有在目标路径的父目录同级，并且加上私有前缀的名称与其相等才可以
			$includeSource = ( strpos( $targetDir, $sourceDir ) === 0 );
			if( $includeSource ) {
				$count = 0;
				// 谨慎点，我把两边的目录分隔符去掉，无论它是否存在
				$replaceDir = trim( str_replace( $sourceDir, '', $targetDir, $count ), DIRECTORY_SEPARATOR );
				$isLastDir = ( ! strpos( $replaceDir, DIRECTORY_SEPARATOR ) );
				$isPublicInterface = ( $this->_privatePre . basename( $_source, '.php' ) === $replaceDir );
				if( $count && $isLastDir && $isPublicInterface ) {
					return true;
				}
			}
		}
		return false;
	}
}