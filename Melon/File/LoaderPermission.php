<?php
/**
 * 
 */

namespace Melon\File;

defined( 'IN_MELON' ) or die( 'Permission denied' );

class LoaderPermission {
	
	protected $_verifyRange = array();
	
	protected $_privatePre;

	public function __construct( array $verifyRange, $privatePre = '_' ) {
		$this->_setRange( $verifyRange );
		$this->_privatePre = $privatePre;
	}
	
	protected function _setRange( array $verifyRange ) {
		foreach( $verifyRange as $path ) {
			$realPath = realpath( $path );
			if( $realPath === false ) {
				throw new \Melon\Exception\RuntimeException( "{$realPath}不是一个有效的路径" );
			}
			$this->_verifyRange[] = $realPath;
		}
	}

	protected function _inRange( $target ) {
		foreach( $this->_verifyRange as $path ) {
			if( strpos( $target, $path ) === 0  ) {
				return true;
			}
		}
		return false;
	}

	public function verify( $source, $target ) {
		// 判断路径是否在检查范围内
		if( ! $this->_inRange( $target ) ) {
			return true;
		}
		$noPrivate = ( strpos( DIRECTORY_SEPARATOR . $this->_privatePre, $target ) === false );
		if( $noPrivate ) {
			return true;
		}
		$sourceDir = dirname( $source ) . DIRECTORY_SEPARATOR;
		$targetDir = dirname( $target ) . DIRECTORY_SEPARATOR;
		if( $sourceDir === $targetDir ) {
			return true;
		}
		$includeTarget = ( strpos( $sourceDir, $targetDir ) === 0 );
		if( $includeTarget ) {
			return true;
		}
		$includeSource = ( strpos( $targetDir, $sourceDir ) === 0 );
		if( $includeSource ) {
			$count = 0;
			$replaceDir = trim( str_replace( $sourceDir, '', $targetDir, $count ), DIRECTORY_SEPARATOR );
			$isLastDir = ( ! strpos( $replaceDir, DIRECTORY_SEPARATOR ) );
			$isPublicInterface = ( $this->_privatePre . basename( $source ) === $replaceDir );
			if( $count && $isLastDir && $isPublicInterface ) {
				return true;
			}
		}
		return false;
	}
}