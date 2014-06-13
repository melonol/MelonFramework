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

defined( 'IN_MELON' ) or die( 'Permission denied' );

/**
 * 判断加载脚本或者文件的权限
 * 
 * 程序给出一组包含路径，当加载的目标文件路径存在这组包含路径中的时候，就会要求检查权限
 * 当目标路径名称含有某个特定的前缀时，它属于同级目录下的特定脚本文件私有的
 * 除了这些脚本文件外，其它人没有权限去读取它们
 * 
 * 类中所使用到的路径参数都必需是一组标准的，没有冗余的系统路径格式
 * 因为程序不会做任何处理，减少realpath的调用
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
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
     * @param array $includePath 包含路径数组，如果'目标路径'存在包含路径中，则'载入源路径'会被检查文件读取权限
     * 即是说包含路径是一组被管辖的范围，标准的系统路径格式
     * @param string $privatePre 私有权限的前缀标识符
     */
    public function __construct( array $includePath, $privatePre = '_' ) {
        $this->_includePath = $includePath;
        $this->_privatePre = $privatePre;
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
     * 我把它们分别叫做'载入源路径'和'目标路径'，当载入源路径满足以下条件时，才有权限载入目标路径
     * 1. 目标路径不在检查范围内，即不在包含路径中
     * 2. 目标路径文件和父目录都不属于私有的
     * 3. 某个父目录属于私有，但是载入源也在这个私有目录或者其子目录下
     * 4. 载入源文件名与目标路径的当前私有目录同级，载入源文件名（不含.php）加上私有前缀与当前父目录相等，比如 File.php和_File
     * 
     * @param string $source 载入源路径，标准的系统路径格式
     * @param string $target 目标路径，标准的系统路径格式
     * @return boolean
     */
    public function verify( $source, $target ) {
        // 准备开始检查权限，我设定如果满足要求，就立刻让程序返回
        // 可能违背了结构化编程原则，但如果要在这里遵守它，多层的if嵌套会让我头晕
        // 我喜欢遵守规则，但不喜欢看上去混乱的东西
        
        // 不在检查范围内？
        if( ! $this->_inRange( $target ) ) {
            return true;
        }
        // 没有私有文件或者目录？
        $noPrivate = ( strpos( $target, DIRECTORY_SEPARATOR . $this->_privatePre ) === false );
        if( $noPrivate ) {
            return true;
        }
        // 我要加上一个目录分隔符做结尾，防止因为包含片段名称（比如'dir'和'directory'）可能导致的一些问题
        $sourceDir = dirname( $source ) . DIRECTORY_SEPARATOR;
        $targetDir = dirname( $target ) . DIRECTORY_SEPARATOR;
        
        // 同级目录？
        if( $sourceDir === $targetDir ) {
            return true;
        }
        // 再确定一下是否是私有文件
        if( strpos( basename( $target ), $this->_privatePre ) !== 0 ) {
            // 如果载入源路径包含了目标路径，则说明载入源在目标路径更里的目录
            // 这样当然是有权限的
            $includeTarget = ( strpos( $sourceDir, $targetDir ) === 0 );
            if( $includeTarget ) {
                return true;
            }
            // 在同一个包下
            if( $this->_packageDir( $source ) === $this->_packageDir( $target ) ) {
                return true;
            }
            // 反过来，只有在目标路径的私有目录同级，并且加上私有前缀的名称与其相等才可以
            $includeSource = ( strpos( $targetDir, $sourceDir ) === 0 );
            if( $includeSource ) {
                $count = 0;
                // 谨慎点，我把两边的目录分隔符去掉，无论它是否存在
                $replaceDir = trim( str_replace( $sourceDir, '', $targetDir, $count ), DIRECTORY_SEPARATOR );
                $noPrivate = ( strpos( $replaceDir, DIRECTORY_SEPARATOR . $this->_privatePre ) === false );
                list( $firstDir ) = explode( DIRECTORY_SEPARATOR, $replaceDir );
                $isPublicInterface = ( $this->_privatePre . basename( $source, '.php' ) === $firstDir );
                if( $count && $noPrivate && $isPublicInterface ) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * 获取路径的包路径
     * 
     * @param string $path 路径
     * @return string 包的路径
     */
    public function _packageDir( $path ) {
        $sourceDir = dirname( $path );
        $parentPos = strrpos( $sourceDir, DIRECTORY_SEPARATOR . $this->_privatePre );
        if( $parentPos ) {
            $spos = ( $parentPos + strlen( DIRECTORY_SEPARATOR ) );
            $epos = strpos( $sourceDir, DIRECTORY_SEPARATOR, $spos );
            if( $epos ) {
                return substr( $sourceDir, 0, $epos );
            }
        }
        return null;
    }
}