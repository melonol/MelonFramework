<?php
//@Template::checkTemplate 
//@Template::checkTemplate 
//@Template::checkTemplate 
?>
<?php

namespace Melon\Base;

defined('IN_MELON') or die('Permission denied');

//支持继承模板
//支持include
//if | if else | foreach | php | lang
//自动检查更新 {php $data = array();}

class Template {
	
	private $_template;
	
	private $_content;
	
	private $_vars;
	
	private $_tags = array();
	
	private $_cachePath;
	
	private $_compileingPath;
	
	private $_beginTag = '{';
	
	private $_endTag = '}';
	
	private $_includeFiles = array();

	public function __construct( $template ) {
		$this->_template = $template;
		$this->_tags = array(
			'list' => array(
				'callable' => '\Melon::callable',
				'args' => array( 'name' => 3, 'id' => null )
			)
		);
	}
	
	static public function checkForUpdates() {
		
	}
	
	private function _getContent( $file ) {
		if( ! file_exists( $file ) ) {
			throw new \Melon\Exception\RuntimeException( "模板文件{$file}不存在" );
		}
		return file_get_contents( $file );
	}
	
	public function assign( $key, $value = null ) {
		
	}
	
	public function setCachePath() {
		
	}
	
	public function setCompilePath() {
		
	}
	
	public function saveCache( $cachePath = null ) {
		
	}
	
	public function saveCompile( $compilePath = null ) {

	}
	
	public function compile(  $template  ) {
		$content =  $this->compileSnippet( $template );
		return $content;
	}
	
	/**
	 * 编译模板
	 * 
	 * @param string $template 模板文件路径
	 * @return string 编译后的内容
	 */
	public function compileSnippet( $template ) {
		$content = $this->_getContent( $template );
		
		$self = $this;
		// 处理简单的标签
		$exp = "/{$this->_beginTag}(\/?[^\n\r]+?){$this->_endTag}/";
		$content = preg_replace_callback( $exp, function( $match ) use ( $self ) {
			// 按使用频率排序
			// 所有单个标签都可以添加'/'做为结束符号，也可以不加
			static $exps = array(
				'\$(.*?)\/?' => '<?php echo \$$1; ?>',
				'if\s+(.*)' => '<?php if($1){ ?>',
				'\/(if|foreach)' => '<?php } ?>',
				'else(\s*\/)?' => '<?php }else{ ?>',
				'foreach\s+([^\s]+)\s*([^\s]+)\s*$' => '<?php foreach($1 as $2){ ?>',
				'foreach\s+([^\s]+)\s*([^\s]+)\s*([^\s]+)\s*$' => '<?php foreach($1 as $2 => $3){ ?>',
				'print\s+(.*?)\/?' => '<?php echo $1; ?>',
				'elseif\s+(.*?)\/?' => '<?php }elseif($1){ ?>',
				'php' => '<?php ',
				'\/php' => '?>',
			);
			$replaceContent = $match[1];
			$count = 0;
			foreach( $exps as $exp => $replace ) {
				$replaceContent = preg_replace( "/^$exp$/i", $replace, $replaceContent, -1, $count );
				// 如果替换成功，表明这是模板标签
				// 我在里面做一些处理，之后立刻返回
				if( $count > 0 ) {
					return $self->compileVar( $replaceContent );
				}
			}
			// 没有匹配的，返回原本的数据
			return $match[0];
		}, $content );
		// 还有两个比较麻烦的标签，我分开处理了
		// 想不到什么办法做得更简单
		$content = $this->compileTag( $content );
		$content = $this->compileInclude( $content, dirname( $template ) );
		
		$this->_includeFiles[] = $template;
		return $content;
	}
	
	/**
	 * 替换include标签
	 * 
	 * 使用include标签可以引入一个子模板，就像PHP的include一样
	 * 
	 * @param string $content 模板内容
	 * @param string $dir 需要指定当前模板的目录，这样include可以处理相对路径
	 * @return string
	 */
	public function compileInclude( $content, $dir ) {
		$self = $this;
		$exp = "/{$this->_beginTag}include\\s+(['\"]?)(.*?)(\\1)\\s*\\/?{$this->_endTag}/i";
		return preg_replace_callback( $exp, function( $match ) use( $self, $dir ) {
			// 解释子模板的路径
			if( \Melon\Base\Func\isAbsolutePath( $match[2] ) ) {
				$includeTemplate = $match[2];
			} else {
				$includeTemplate = $dir . DIRECTORY_SEPARATOR . $match[2];
			}
			if( ! file_exists( $includeTemplate ) ) {
				throw new \Melon\Exception\RuntimeException( "子模板{$match[2]}不存在" );
			}
			// 编译子模板
			return $self->compileSnippet( $includeTemplate );
		}, $content );
	}
	
	/**
	 * 替换自定义标签
	 * 
	 * 自定义标签是一个可扩展的功能，可以方便制定一些像CMS模板标签的功能
	 * 
	 * @param string $content
	 * @return string
	 * @throws \Melon\Exception\RuntimeException
	 */
	public function compileTag( $content ) {
		$tags = $this->_tags;
		$exp = "/{$this->_beginTag}tag:(\\w+)(?:{$this->_endTag}|(\\s+.*?){$this->_endTag})/i";
		$self = $this;
		$content = preg_replace_callback( $exp, function( $match ) use( $self, $tags ) {
			// 标签名
			$tagName = $match[1];
			if( ! isset( $tags[ $tagName ] ) ) {
				throw new \Melon\Exception\RuntimeException( "没有定义{$tagName}模板标签" );
			}
			// 要被传入的参数
			$exportArgs = '';
			// 返回结果的变量名
			$resultName = 'data';
			// 如果标签有参数，进一步处理
			if( isset( $match[2] ) ) {
				$matchArgs = array();
				// 解释这些参数，不过有点棘手
				// 因为我允许参数值可以带引号，也可以不带，所以要分两种类型处理
				// 这样产生了两个正则分组（稍后会从这两个分组里面取值）
				preg_match_all( "/(\\w+)\\s*=\\s*+(?:(['\"])(.+?)\\2|([^'\"][^\\s]+))/", $match[2], $matchArgs );
				if( ! empty( $matchArgs[1] ) ) {
					$args = array();
					// 取到这些参数后，因为值有两种类型
					// 所以如果不在第一种类型中，则去另一个类型里取
					foreach( $matchArgs[1] as $index => $name ) {
						$args[ $name ] = ( ! empty( $matchArgs[4][ $index ] ) ?
							$matchArgs[4][ $index ] : $matchArgs[3][ $index ] );
					}
					// 校对参数所在的位置，生成相应的'传入参数'的字符串
					foreach( $tags[ $tagName ]['args'] as $argName => $defaultValue ) {
						$value = ( isset( $args[ $argName ] ) ? $args[ $argName ] : $defaultValue );
						// 允许参数值使用变量，这样会很灵活，而且在嵌套标签里很有用
						if( substr( $value, 0, 1 ) === '$' ) {
							$exportArgs .= $self->compileVar( $value ) . ',';
						}
						// 正常情况，要注意参数值可能里包含了引号，这样就会产生意外的语法错误
						// 我使用单引号标注参数值，所以只转义单引号就行
						else {
							$exportArgs .= '\'' . addcslashes( $value, '\'' ) . '\',';
						}
					}
					// 别忘记去掉右边的逗号
					$exportArgs = rtrim( $exportArgs, ',' );
					// 标签可以通过'result'参数自定义返回结果的变量名
					$resultName = ( isset( $args['result'] ) ? $args['result'] : $resultName );
				}
			}
			// 单标签的话，直接输出执行结果
			if( substr( $match[0], -2, 1 ) === '/' ) {
				return "<?php echo {$tags[ $tagName ]['callable']}({$exportArgs}); ?>";
			}
			// 否则就用遍历了
			return "<?php foreach({$tags[ $tagName ]['callable']}({$exportArgs}) as \${$resultName}) { ?>";
		}, $content );
		
		// 处理结束标签
		$content = preg_replace( "/{$this->_beginTag}\\/tag:(\\w+){$this->_endTag}/i", '<?php } ?>', $content );
		// over
		return $content;
	}
	
	/**
	 * 替换标签里面的变量
	 * 
	 * 使得标签里可以像Javascript那样写变量： $arr.data.val
	 * 甚至可以这样 $arr['data'].val、$arr1.data[ $arr2.data ]
	 * 
	 * @param string $content 需要被替换的内容
	 * @return string
	 */
	public function compileVar( $content ) {
		$content = preg_replace( "/\.([a-zA-Z_]+)(?![^\[]*['\"]\s*\])/", '[\'$1\']', $content );
		return $content;
	}
	
	public function fetch() {
		
	}
	
	public function display() {
		$file = \Melon::env('root') . '/Melon/Data/complie.php';
		$this->_content = $this->compile( $this->_template );
		file_put_contents($file, $this->_content);
		include $file;
	}
}
