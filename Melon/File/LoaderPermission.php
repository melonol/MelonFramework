<?php
/**
 * 
 */

namespace Melon\File;
use \Melon\Exception;

defined( 'IN_MELON' ) or die( 'Permission denied' );

class LoaderPermission {
	
	protected $_includePath = array();
	
	protected $_privatePre;

	public function __construct( array $includePath, $privatePre = '_' ) {
		$this->_setIncludePath( $includePath );
		$this->_privatePre = $privatePre;
	}
	
	protected function _setIncludePath( array $includePath ) {
		foreach( $includePath as $path ) {
			$realPath = realpath( $path );
			if( $realPath === false ) {
				throw new Exception\RuntimeException( "{$path}不是一个有效的路径" );
			}
			$this->_includePath[] = $realPath;
		}
	}

	protected function _inRange( $target ) {
		foreach( $this->_includePath as $path ) {
			if( strpos( $target, $path ) === 0  ) {
				return true;
			}
		}
		return false;
	}

	public function verify( $source, $target ) {
		$_source = realpath( $source );
		$_target = realpath( $target );
		if( ! $_source || ! $_target ) {
			$errorFile = ( $_source ? $target : $source );
			throw new Exception\RuntimeException( "{$errorFile}不是一个有效的文件" );
		}
		// 判断路径是否在检查范围内
		if( ! $this->_inRange( $_target ) ) {
			return true;
		}
		$noPrivate = ( strpos( $_target, DIRECTORY_SEPARATOR . $this->_privatePre ) === false );
		if( $noPrivate ) {
			return true;
		}
		$sourceDir = dirname( $_source ) . DIRECTORY_SEPARATOR;
		$targetDir = dirname( $_target ) . DIRECTORY_SEPARATOR;
		if( $sourceDir === $targetDir ) {
			return true;
		}
		if( strpos( basename( $_target ), $this->_privatePre ) !== 0 ) {
			$includeTarget = ( strpos( $sourceDir, $targetDir ) === 0 );
			if( $includeTarget ) {
				return true;
			}
			$includeSource = ( strpos( $targetDir, $sourceDir ) === 0 );
			if( $includeSource ) {
				$count = 0;
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