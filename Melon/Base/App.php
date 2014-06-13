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
use \Melon\Http;

defined('IN_MELON') or die('Permission denied');

/**
 * APP模式基础运行类
 * 
 * 负责初始化信息、创建APP和模块以及加载它们运行
 * APP模式默认是MVC，但这是可更改的，它只负责解析路由得到控制器、方法和参数的信息
 * 并不涉及具体操作
 */
class App {
    
    /**
     * Melon扣肉
     * 
     * 需要它提供一些基础的工具方便初始化
     * 
     * @param \Melon\Base\Core $core
     */
    protected $_core;
    
    /**
     * 构造器
     * 
     * @param \Melon\Base\Core $core
     */
    public function __construct( Core $core ) {
        $this->_core = $core;
    }
    
    /**
     * 初始化
     * 
     * 这个会改变扣肉的一些环境变量（env）
     * 
     * @param array $config 配置
     * @return void
     * @throws Exception\RuntimeException
     */
    public function init( $config ) {
        $nameRule = '/^[a-zA-Z_]+\w*$/';
        $appName = ( isset( $config['appName'] ) && $config['appName'] ?
                $config['appName'] : null );
        if( ! $appName ) {
            throw new Exception\RuntimeException( '没有指定app' );
        }
        if( ! preg_match( $nameRule, $appName ) ) {
            throw new Exception\RuntimeException( 'app名称必需为字母开头，并由字母、数字或下划线组成' );
        }
        $this->_core->env['appName'] = ucfirst( $appName );
        $className = $this->_core->env['appName'];
        $this->_core->env['className'] = $this->_core->env['appName'];
        $this->_core->env['appDir'] = $this->_core->env['root'] . DIRECTORY_SEPARATOR . $this->_core->env['className'];
        
        if( isset( $config['moduleName'] ) ) {
            $this->_setModule( $config['moduleName'] );
        }
        // 安装APP
        if( $this->_core->env['install'] === 'app' ) {
            $this->_createApp();
        }
        
        if( ! file_exists( $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . $this->_core->env['className'] . '.php' ) ) {
            throw new Exception\RuntimeException( "{$this->_core->env['appName']} app不存在，你需要使用install参数安装它" );
        }
        // 载入基础配置
        $defaultConfig = require ( $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . '__APPNAME__' . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Base.php' );
        $appConfig = require ( $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Base.php' );
        // 合并核心配置
        if( isset( $config['config'] ) && is_array( $config['config'] ) ) {
            $this->_core->conf = array_replace_recursive( $this->_core->conf, $defaultConfig, $appConfig, $config['config'] );
        } else {
            $this->_core->conf = array_replace_recursive( $this->_core->conf, $defaultConfig, $appConfig );
        }
        // 将日志目录转到app
        $_logDir = $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . $this->_core->conf['logDir'];
        $logDir = Func\isAbsolutePath( $this->_core->conf['logDir'] ) ? $this->_core->conf['logDir'] : $_logDir;
        $this->_core->logger = new Logger( $logDir, 'runtime', $this->_core->conf['logSplitSize'] );
        
        // 载入APP的主体类
        $this->_core->load( __FILE__, $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . $this->_core->env['className'] . '.php' );
    }
    
    /**
     * 运行APP模式
     * 
     * 你必需在框架初始时把type设定为app，和提供一些必要的信息才能运行
     * 当你提供一个install为app的参数时，表示要安装以配置参数中appName为名字的APP
     * 当你提供一个install为module的参数时，表示要安装以配置参数中moduleName为名字module
     * 
     * 正如这个类的介绍一样，它只负责解析路由得到控制器、方法和参数的信息
     * 具体操作交由当前运行的module自行处理
     * 
     * @param string $module [可选] module名称，如果你在初始化的时候设置了，这时候就不需要指定此参数
     * @param string $controller [可选] 控制器，如果不提供此参数，程序则调用Route类尝试解释路径
     * @param string $action [可选] 方法，必需先提供控制器，否则该选项无效
     * @param string $args [可选] 参数，必需先提供控制器，否则该选项无效
     * @return void
     * @throws Exception\RuntimeException
     */
    public function run( $module = null, $controller = null, $action = null, array $args = array() ) {
        if( $this->_core->env['runType'] !== 'app' ) {
            throw new Exception\RuntimeException( '当前模式不能运行app' );
        }
        $_module = $module;
        
        // 取得路由配置
        $defaultConfig = require ( $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . '__APPNAME__' . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Route.php' );
        $routeConfig = $this->_core->acquire( __FILE__, $this->_core->env['appDir'] . DIRECTORY_SEPARATOR .
            'Conf' . DIRECTORY_SEPARATOR . 'Route.php' );
        $routeConfig = array_replace_recursive( $defaultConfig, $routeConfig );
        $this->_core->env['routeConfig'] = &$routeConfig;
        
        // 如果直接提供了路由器，则直接处理，忽略配置
        if( $controller ) {
            $pathInfo = array(
                'controller' => $controller,
                'action' => ( ! $action ? $action :
                    ( isset( $routeConfig['defaultAction'] ) ? $routeConfig['defaultAction'] : null ) ),
                'args' => $args,
            );
        } else {
            $map = array(
                'incompletePathinfo' => Http\Route::TYPE_INCOMPLETE_PATHINFO,
                'completePathinfo' => Http\Route::TYPE_COMPLETE_PATHINFO,
                'requestKey' => Http\Route::TYPE_REQUEST_KEY,
            );
            $type = ( isset( $map[ $routeConfig['type'] ] ) ? $map[ $routeConfig['type'] ] : 'requestKey' );
            $route = \Melon::httpRoute( $routeConfig, $type, $routeConfig['requestKey'] );
            $parsed = $route->parse();
            $_pathInfo = ( $parsed ? explode( '/', $parsed ) : array() );
            
            // 如果没有指定模块，并且假设第一个参数是模块的条件成立时
            // 将取其值为模块
            if( ! $_module && $_pathInfo ) {
                if( file_exists( $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . 'Module' . DIRECTORY_SEPARATOR . ucfirst( $_pathInfo[0] ) . '.php' ) ) {
                    $_module = array_shift( $_pathInfo );
                }
            } // else controller
            
            // 整理一下
            $pathInfo = array(
                'controller' => ( isset( $_pathInfo[0] ) ? $_pathInfo[0] :
                    ( isset( $routeConfig['defaultController'] ) ? $routeConfig['defaultController'] : null ) ),
                'action' => ( isset( $_pathInfo[1] ) ? $_pathInfo[1] :
                    ( isset( $routeConfig['defaultAction'] ) ? $routeConfig['defaultAction'] : null ) ),
                'args' => ( isset( $_pathInfo[2] ) ? array_splice( $_pathInfo, 2 ) : array() ),
            );
        }
        
        if( ! $_module && isset( $routeConfig['defaultModule'] ) ) {
            $_module = $routeConfig['defaultModule'];
        }
        $this->_core->env['controller'] = ucfirst( $pathInfo['controller'] );
        $this->_core->env['action'] = $pathInfo['action'];
        $this->_core->env['args'] = $pathInfo['args'];
        
        // 搞定后清理掉不再用的数据
        unset( $routeConfig, $_pathInfo );
        
        // 设置和安装模块
        $this->_setModule( $_module );
        if( $this->_core->env['install'] === 'module' || $this->_core->env['install'] === 'app' ) {
            $this->_createModule();
        }

        // 现在把控制权交给当前请求的模块
        $moduleClass = $this->_core->env['className'] . '\Module\\' . $this->_core->env['moduleName'];
        if( ! file_exists( $this->_core->env['root'] . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $moduleClass ) . '.php' ) ) {
            throw new Exception\RuntimeException( "{$this->_core->env['moduleName']} module不存在，你需要使用install参数安装它" );
        }
        $command = new $moduleClass();
        $command->execute( $pathInfo['controller'], $pathInfo['action'], $pathInfo['args'] );
    }
    
    /**
     * 设置module
     * 
     * @param string $name module名称
     */
    protected function _setModule( $name = null ) {
        if( ! $name ) {
            throw new Exception\RuntimeException( '没有指定module' );
        }
        $nameRule = '/^[a-zA-Z_]+\w*$/';
        if( ! preg_match( $nameRule, $name ) ) {
            throw new Exception\RuntimeException( 'module名称必需为字母开头，并由字母、数字或下划线组成' );
        }
        $this->_core->env['moduleName'] = ucfirst( $name );
    }


    /**
     * 创建一个APP到root目录下
     * 
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function _createApp() {
        // 要做一些检查，防止覆盖已有的文件，这样我才不会被人骂得狗血淋头
        if( ! is_writable( $this->_core->env['root'] ) ) {
            throw new Exception\RuntimeException( "app目录{$this->_core->env['root']}不存在或不可写" );
        }
        if( is_dir( $this->_core->env['appDir'] ) && ! $this->_isEmptyDir( $this->_core->env['appDir'] ) ) {
            throw new Exception\RuntimeException( "app目录{$this->_core->env['appDir']}不为空，无法创建。如果你已经创建成功，请在初始化中关闭install参数" );
        }
        // 要创建一个临时目录，把要创建的文件放到里面，因为要进行一些修改
        // 保险起见，我先清空一次临时目录
        $this->_cleanTempDir();
        $tempDir = $this->_createTempDir( 'App' );
        $this->_copyDir( $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'App', $tempDir );
        // 替换预定义变量
        $this->_replaceVar( $tempDir );
        // 放到root目录下
        $this->_copyDir( $tempDir, $this->_core->env['root'] );
        // 搞定，清空临时目录
        $this->_cleanTempDir();
    }
    
    /**
     * 创建一个module到APP的module目录下
     * 
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function _createModule() {
        // 先做检查，小心覆盖已有文件
        $parentModuleDir = $this->_core->env['appDir'] . DIRECTORY_SEPARATOR . 'Module';
        if( ! is_writable( $parentModuleDir ) ) {
            throw new Exception\RuntimeException( "module根目录{$parentModuleDir}不存在或不可写" );
        }
        $moduleDir = $parentModuleDir . DIRECTORY_SEPARATOR . $this->_core->conf['privatePre'] . $this->_core->env['moduleName'];
        $moduleEntry = $parentModuleDir . DIRECTORY_SEPARATOR . $this->_core->env['moduleName'] . '.php';
        if( is_dir( $moduleDir ) || file_exists( $moduleEntry ) ) {
            throw new Exception\RuntimeException( "module {$moduleDir}已存在，无法创建。如果你已经创建成功，请在初始化中关闭install参数" );
        }
        
        // 要创建一个临时目录，把要创建的文件放到里面，因为要进行一些修改
        // 先清空一次临时目录
        $this->_cleanTempDir();
        $tempDir = $this->_createTempDir( 'Module' );
        $this->_copyDir( $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'Module', $tempDir );
        // 替换预定义变量
        $this->_replaceVar( $tempDir );
        // 放到module目录下
        $this->_copyDir( $tempDir, $parentModuleDir );
        // 搞定
        $this->_cleanTempDir();
    }
    
    /**
     * 递归替换目录和文件（包括名字和内容）中指定的变量，包括下面几个
     * 
     * 变量                   替换值
     * __APPNAME__            当前运行的APP名字
     * __MODULENAME__         当前运行的模块名字
     * __PRIVATE_PRE__        私有前缀
     * 
     * @param string $dir 目录
     * @param array $ignore 要忽略的关键字，它们将不会被替换
     * @return void
     */
    private function _replaceVar( $dir, array $ignore = array() ) {
        $replace = array(
            '__APPNAME__' => ( isset( $this->_core->env['appName'] ) ? $this->_core->env['appName'] : null ),
            '__MODULENAME__' => ( isset( $this->_core->env['moduleName'] ) ? $this->_core->env['moduleName'] : null ),
            '__PRIVATE_PRE__' => ( isset( $this->_core->conf['privatePre'] ) ? $this->_core->conf['privatePre'] : null ),
        );
        foreach( $replace as $key => $value ) {
            if( ! in_array( $key, $ignore ) && $value ) {
                $this->_replaceContent( $dir, $key, $value );
            }
        }
    }
    
    /**
     * 创建一个临时目录
     * 
     * @param string $subDirName 在这个临时目录下创建指定的子目录
     * @return string 临时目录下$subDirName子目录路径
     */
    private function _createTempDir( $subDirName ) {
        $tempDir = $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'InstallTemp' . DIRECTORY_SEPARATOR . $subDirName;
        if( ! is_dir( $tempDir ) ) {
            mkdir( $tempDir, 0777, true );
        }
        return $tempDir;
    }
    
    /**
     * 清空临时目录
     * 
     * @return void
     */
    private function _cleanTempDir() {
        $tempDir = $this->_core->env['melonLibrary'] . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'InstallTemp';
        if( is_dir( $tempDir ) ) {
            $this->_deldir( $tempDir );
        }
    }
    
    /**
     * 判断一个目录是否为空
     * 
     * @param string $dir 目录名字
     * @return boolean
     */
    private function _isEmptyDir( $dir ) {
        return ( count( scandir( $dir ) ) <= 2 );
    }
    
    /**
     * 递归复制一个目录
     * 
     * @param string $source 源目录
     * @param string $target 目标目录
     * @return boolean
     */
    private function _copyDir( $source, $target ) {
        if( ! is_dir( $target ) ) {
            mkdir( $target, 0777 );
        }
        $handle = dir( $source );
        if(!$handle) {
            return false;
        }
        while( $entry = $handle->read() ) {
            if( $entry !== '.' && $entry !== '..' ) {
                if( is_dir( $source . DIRECTORY_SEPARATOR . $entry ) ) {
                    $this->_copyDir( $source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry );
                }
                // 过滤被忽略的文件，它们存在的目的是为了让当前目录成功添加到版本库
                elseif( $entry !== '.ignore' ) {
                    copy( $source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry );
                }
            }
        }
        $handle->close();
    }
    
    /**
     * 删除一个目录，包括它自身
     * 
     * @param string $dir 目录路径
     * @return boolean
     */
    private function _deldir( $dir ) {
        //先删除目录下的文件：
        $handle = dir( $dir );
        if( ! $handle ) {
            return false;
        }
        while( $entry = $handle->read() ) {
            if( $entry !== '.' && $entry !== '..' ) {
                $fullpath = $dir . DIRECTORY_SEPARATOR . $entry;
                if( is_dir( $fullpath ) ) {
                    $this->_deldir( $fullpath );
                } else {
                    unlink( $fullpath );
                }
            }
        }
        $handle->close();
        //删除当前文件夹：
        return rmdir( $dir );
    }
    
    /**
     * 递归替换目录和文件（包括名字和内容）中指定的变量
     * 
     * @param string $target 要替换的目录或文件路径
     * @param string $keyWord 关键字
     * @param string $replace 替换内容
     * @return boolean
     */
    private function _replaceContent( $target, $keyWord, $replace ) {
        $handle = dir( $target );
        if(!$handle) {
            return false;
        }
        while( $entry = $handle->read() ) {
            if( $entry !== '.' && $entry !== '..' ) {
                $replacedEntry = str_replace( $keyWord, $replace, $entry );
                $path = $target . DIRECTORY_SEPARATOR . $replacedEntry;
                rename( $target . DIRECTORY_SEPARATOR . $entry, $path );
                if( is_dir( $path ) ) {
                    $this->_replaceContent( $path, $keyWord, $replace );
                }
                else {
                    $contents = file_get_contents( $path );
                    $replacedContents = str_replace( $keyWord, $replace, $contents );
                    file_put_contents( $path, $replacedContents );
                }
            }
        }
        $handle->close();
        return true;
    }
}