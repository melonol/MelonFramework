<table style="padding: 5px; border-collapse: collapse;">
    <tr>
        <th style="border: 1px solid #000; padding: 5px; text-align: left;">[-]/var/www/framework/</th>
    </tr>
    <tr>
        <td style="border: 1px solid #000; padding: 5px;">
            <a href="javascript:void(0);" style="color: #000; text-decoration: none;" onclick="
                var codeMain = this.parentNode.getElementsByTagName('div')[0],
                    codeStatus = this.getElementsByTagName('span')[0];
                if(codeMain.style.display == 'block') {
                    codeMain.style.display = 'none';
                    codeStatus.innerHTML = '[+]'
                } else {
                    codeMain.style.display = 'block';
                    codeStatus.innerHTML = '[-]';
                }
            "><span>[+]</span>/var/www/framework</a>
            <div style="width: 600px; margin-left: 20px; overflow: hidden; display: none;">
            </div>
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #000; padding: 5px;"><span>&nbsp;&nbsp;&nbsp;</span>/var/www/framework</td>
    </tr>
</table>
<?php

define( 'IN_MELON', true );

use Melon\Base;
use Melon\Cache;
use Melon\Exception;
use Melon\File;
use Melon\Util;
use Melon\Database;

set_exception_handler(function($exception) {
	echo codeSnippet($exception->getFile(), $exception->getLine());
});

		
function codeSnippet( $file, $focus, $range = 7, $style = array( 'lineHeight' => 20, 'fontSize' => 13 ) ) {
	$html = @highlight_file( $file, true );
	if( ! $html ) {
		return false;
	}
	$br = '<br />';
	$html_lines = explode( $br, $html );
	unset($html);
	$lines_count = count( $html_lines );
	$line_html = '';
	$code_html = '';

	//获取前五行的范围
	$start = ( ( $focus - $range ) < 1 ? 1 : ($focus - $range) );
	$end = ( ( $focus + $range ) > $lines_count ? $lines_count : ( $focus + $range ) );
	for( $line = ( $start - 1 ); $line < $end; $line++ ) {
		$index_pad = str_pad( $line + 1, strlen( $lines_count ), 0, STR_PAD_LEFT );
		$line_html .= $index_pad . $br;
		$code_html .= $html_lines[ $line ] . $br;
	}
	
	//是否缺少开始标签
	if( substr( $code_html, 0, 5 ) !== '<span' ) {
		$index = $start - 1;
		while( $index > 0 ) {
			$match = array();
			preg_match( '/<span style="color: #([\w]+)"(.(?!<\/span>))+$/', $html_lines[ --$index ], $match );
			if( ! empty( $match ) ) {
				$code_html = "<span style=\"color: #{$match[1]}\">" . $code_html;
				break;
			}
		}
	}
	//是否缺少结束标签
	if( substr( $code_html, -7 ) !== '</span>' ) {
		$code_html .= '</span>';
	}
	
	$hight_line_posistion = ( ( $focus - $start ) * $style['lineHeight'] );
	return <<<EOT
        <div style="position: relative; font-size: {$style['fontSize']}px;">
            <span style="display: block; position: absolute; top: {$hight_line_posistion}px; height: {$style['lineHeight']}px; width: 100%; _width: 95%; background-color: yellow; opacity: 0.4; filter:alpha(opacity=40); z-index: -1; "></span>
            <div style="float: left; margin-right: 10px; line-height: {$style['lineHeight']}px; color: #aaa;">{$line_html}</div>
            <div style="_width: 95%; line-height: {$style['lineHeight']}px; overflow: hidden; white-space:nowrap; text-overflow:ellipsis;">{$code_html}</div>
        </div>
EOT;
}
throw new \Exception('test');
class Melon {
	
	static private $_melon;

	final protected function __construct() {
		;
	}
	
	static public function init() {
		if( defined( 'MELON_INIT' ) ) {
			return;
		}
		
		// 注册autoload
		// 不过现在它还不能用，一些必要的数据还没初始化
		// 放在前面是为了让你能看到它的存在 :)
		spl_autoload_register( '\Melon::autoload' );
		
		// 我把属性都放到$_melon变量中，因为Melon很可能会被扩展（继承）
		// 为了方便，我都是用了self来读取属性和方法
		// 因为如果属性太多，到时和子类的属性冲突的机率就越大
		// 用单属性的话，到时只需要管住它就可以了
		$melon = self::$_melon = new \stdClass();
		
		// env负责保存一些系统基本的信息
		$melon->env = array(
			'root' => __DIR__,
			'library' =>  __DIR__  . DIRECTORY_SEPARATOR . 'Melon',
		);
		
		// 载入基础配置
		$melon->conf = require ( $melon->env['library'] . DIRECTORY_SEPARATOR .
				'Data' . DIRECTORY_SEPARATOR . 'Conf' . DIRECTORY_SEPARATOR . 'Base.php' );
		// includePath是loader － 包括autoload、权限审查等函数的工作范围
		// 需要把MELON的基础目录添加到includePath中
		$melon->conf['includePath'][] = $melon->env['root'];
		$melon->env['config'] = &$melon->conf;
		
		// 设置时间
		if( ! empty( $melon->conf['timezone'] ) ) {
			date_default_timezone_set( $melon->conf['timezone'] );
		}
		$microtime = microtime( true );
		$melon->env['time'] = intval( $microtime );
		$melon->env['microtime'] = $microtime;
		
		// 初始化loader
		self::_initLoader();
		
		define( 'MELON_INIT', true );
	}
	
	/**
	 * 初始化loader
	 * 它是一些加载脚本或者文件的必需前提条件
	 * 
	 * @return void
	 */
	private function _initLoader() {
		$library = self::$_melon->env['library'] . DIRECTORY_SEPARATOR;
		// 现在准备一些必需的类
		$autoload = array(
			$library . 'Util' . DIRECTORY_SEPARATOR . 'Set.php',
			$library . 'File' . DIRECTORY_SEPARATOR . 'LoaderSet.php',
			$library . 'File' . DIRECTORY_SEPARATOR . 'PathTrace.php',
			$library . 'File' . DIRECTORY_SEPARATOR . 'LoaderPermission.php',
		);
		// 用一个数组来保存上面的类的信息
		// 因为等下我要告诉loader，它们已经被载入过了，不要重复载入
		$scripts = array();
		// MELON_TEST是我做单元测试的时候创建的
		// 直接整合进来有点不太好，不过这是最简单的方式
		if( defined( 'MELON_TEST' ) ) {
			foreach( $autoload as $script ) {
				require_once $script;
				$scripts[ $script ] = $script;
			}
		} else {
			foreach( $autoload as $script ) {
				require $script;
				$scripts[ $script ] = $script;
			}
		}
		
		// 我需要一个保存已载入的脚本文件信息的对象
		// 这样可以不需要使用include_once或者require_once，也可以达到它们那样的效果
		// 把刚才已加载的类的信息添加进去
		self::$_melon->loaderSet = new File\LoaderSet( $scripts,
			File\LoaderSet::REPLACE_NOT );
		// 载入文件时还需要一个权限审查对象
		self::$_melon->loaderPermission = new File\LoaderPermission(
			self::$_melon->conf['includePath'], self::$_melon->conf['privatePre']
		);
	}





	/******************************************************************
	 * 普通加载
	 ******************************************************************/
	
	/**
	 * 载入一个脚本
	 * 
	 * 它可以像require_once一样防止重复载入同一个脚本
	 * 
	 * @param string $script 脚本路径，你可以使用相对路径，程序会自动将其转为绝对路径
	 * @return void
	 * @throws Exception\RuntimeException
	 */
	final static public function load( $script ) {
		$load = File\PathTrace::parse( $script, true );
		if( ! $load ) {
			throw new Exception\RuntimeException( "无法识别{$script}脚本文件" );
		}
		self::_load( $load['source'], $load['target'] );
	}
	
	/**
	 * 自动加载类
	 * 
	 * 它被注册到spl_autoload_register函数，所以你不需要手动调用它
	 * 当调用的类不存在时会自动触发
	 * 需要注意的是它是在includePath中查找类文件的，并且以类的命名空间作为目录
	 * 
	 * @param string $class 完整的类名
	 * @return void
	 */
	final static public function autoLoad( $class ) {
		$file = str_replace( '\\', DIRECTORY_SEPARATOR, $class ) . '.php';
		foreach( self::$_melon->conf['includePath'] as $path ) {
			$script = realpath( $path . DIRECTORY_SEPARATOR . $file );
			if( $script ) {
				self::_load( File\PathTrace::sourceFile(), $script );
			}
		}
	}
	
	/**
	 * load和autoLoad逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标脚本路径
	 * @return void
	 * @see \Melon::load
	 * @see \Melon::autoLoad
	 * @throws Exception\RuntimeException
	 */
	final static private function _load( $source, $target ) {
		$loaded = self::$_melon->loaderSet->has( $target );
		if( ! $loaded && ! is_file( $target ) ) {
			throw new Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			throw new Exception\RuntimeException( "{$source}脚本文件没有权限载入{$target}" );
		}
		if( ! $loaded ) {
			include $target;
			self::$_melon->loaderSet->set( $target, $target );
		}
	}
	
	/**
	 * 获取载入脚本文件时返回的数据
	 * 
	 * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
	 * 它不会像{@link Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static public function acquire( $script ) {
		$load = File\PathTrace::parse( $script, true );
		if( ! $load ) {
			// TODO::改为抛出警告
			throw new Exception\RuntimeException( "无法识别{$script}脚本" );
		}
		return self::_acquire( $load['source'], $load['target'] );
	}
	
	/**
	 * acquire逻辑的主要实现
	 * 
	 * @param string $source 载入源脚本路径
	 * @param string $target 目标路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static private function _acquire( $source, $target ) {
		if( ! is_file( $target ) ) {
			// TODO::改为抛出警告
			throw new Exception\RuntimeException( "{$target}不是一个文件，不能载入它" );
		}
		if( ! self::$_melon->loaderPermission->verify( $source, $target ) ) {
			// TODO::改为抛出警告
			throw new Exception\RuntimeException( "{$source}脚本文件没有权限载入{$target}" );
		}
		return ( include $target );
	}
	
	final static public function file() {
	}
	
	/******************************************************************
	 * 包加载
	 ******************************************************************/
	
	/**
	 * 从包中载入一个脚本
	 * 
	 * 和{@link Melon::load}一样，它也会防止重复载入同一个脚本
	 * 
	 * @param string $script 脚本路径，必需是相对于包的路径
	 * @return void
	 * @throws Exception\RuntimeException
	 */
	final static public function packageLoad( $script ) {
		$source = File\PathTrace::sourceFile();
		$packageDir = self::_packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		self::_load( $source, $target );
	}
	
	/**
	 * 从包中获取载入脚本文件时返回的数据
	 * 
	 * 经常用在载入配置文件、语言包等直接返回原生PHP数组的脚本文件
	 * 它不会像{@link Melon::load}那样，可以防止重复载入同一个脚本文件
	 * 
	 * @param string $script 脚本路径，必需是相对于包的路径
	 * @return mixed
	 * @throws Exception\RuntimeException
	 */
	final static public function packageAcquire( $script ) {
		$source = File\PathTrace::sourceFile();
		$packageDir = self::_packageDir( $source );
		$target = realpath( dirname( $source ) . DIRECTORY_SEPARATOR . $script );
		if( ! $target ) {
			throw new Exception\RuntimeException( "无法在{$packageDir}目录中找到{$script}脚本文件" );
		}
		return self::_acquire( $source, $target );
	}
	
	/**
	 * 获取当前脚本所在的包的路径
	 * 
	 * @return string 包的路径
	 */
	final static public function packageDir() {
		return self::_packageDir( File\PathTrace::sourceFile() );
	}
	
	/**
	 * packageDir逻辑的主要实现
	 * 
	 * @param string $source 载入源路径
	 * @return string 包的路径
	 */
	final static private function _packageDir( $source ) {
		$sourceDir = dirname( $source );
		$parentPos = strrpos( $sourceDir, DIRECTORY_SEPARATOR . self::$_melon->conf['privatePre'] );
		if( $parentPos ) {
			$spos = ( $parentPos + strlen( DIRECTORY_SEPARATOR ) );
			$epos = strpos( $sourceDir, DIRECTORY_SEPARATOR, $spos );
			if( $epos ) {
				return substr( $sourceDir, 0, $epos );
			}
		}
		// TODO::抛出警告
		return null;
	}
	
	/**
	 * 获取框架的一些基本信息
	 * 
	 * @param string $var [可选] 指定获取哪个值，如果不填此项，则返回所有
	 * @return mixed
	 */
	static public function env( $var = null ) {
		return is_null( $var ) ? self::$_melon->env : 
			( isset( self::$_melon->env[ $var ] ) ? self::$_melon->env[ $var ] : null );
	}
	
	static public function lang() {
		
	}
	
	static public function cache() {
		
	}
	
	final static public function run() {
		
	}
}

Melon::init();
class cms extends Melon {
	static public function init() {
		parent::init();
		print_r( self::$_melon );
	}
}
Melon::load('aaa');

