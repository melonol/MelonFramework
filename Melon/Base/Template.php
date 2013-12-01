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
	
	private $_subTemplate;
	
	private $_content;
	
	private $_vars;
	
	private $_tags = array();
	
	private $_cachePath;
	
	private $_compileingPath;
	
	private $_beginTag = '{';
	
	private $_endTag = '}';

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
	
	private function _getContent( $template ) {
		if( ! file_exists( $template ) ) {
			throw new \Melon\Exception\RuntimeException( "模板文件{$template}不存在" );
		}
		return file_get_contents( $template );
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
		
		// 处理几个比较麻烦的标签
		// 先把内容继承过来
		$content = $this->compileExtend( dirname( $template ), $content );
		// 引入子模板
		$content = $this->compileInclude( $content, dirname( $template ) );
		// 自定义标签
		$content = $this->compileTag( $content );
		
		// 还有一些简单好用的标签
		$exp = "/{$this->_beginTag}(\/?[^\n\r]+?){$this->_endTag}/i";
		$content = preg_replace_callback( $exp, function( $match ) {
			// 按使用频率排序（我主观认为的）
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
				'(block\s+\w+|\/block)' => '', 
				'php' => '<?php ',
				'\/php' => '?>',
				'php\s+(.*?)\/?' => '<?php $1; ?>',
			);
			$replaceContent = $match[1];
			$count = 0;
			foreach( $exps as $exp => $replace ) {
				$replaceContent = preg_replace( "/^$exp$/i", $replace, $replaceContent, -1, $count );
				// 如果替换成功，表明这是模板标签
				// 立刻返回
				if( $count > 0 ) {
					return $replaceContent;
				}
			}
			// 没有匹配的，返回原本的数据
			return $match[0];
		}, $content );
		return $content;
	}
	
	
	public function compileExtend( $dir, $content ) {
		$extendContent = $this->_compileExtendSnippet( $dir, $content );
		// 把标签清掉
		return preg_replace( "/{$this->_beginTag}(block\s+\w+|\/block){$this->_endTag}/i", '', $extendContent );
	}
	
	private function _compileExtendSnippet( $dir, $content ) {
		$b = $this->_beginTag;
		$e = $this->_endTag;
		// 检查是否有声明继承
		$exp = "/^[\\s\\t\\r\\n]*{$b}extend\\s+(['\"]?)(.*?)(\\1)\\s*\\/?{$e}/is";
		$match = array();
		if( preg_match( $exp, $content, $match ) ) {
			// 取得继承模板
			$template = $match[2];
			if( file_exists( $dir . DIRECTORY_SEPARATOR . $template ) ) {
				$template = $dir . DIRECTORY_SEPARATOR . $template;
			}
			// 获取内容
			$parentContent = $this->_getContent( $template );
			// 向上递归继承
			$parentContent = $this->_compileExtendSnippet( dirname( $template ), $parentContent );
			// 分割块
			$blockExp = "/(?={$b}(block\\s+\\w+|\/block){$e})/i";
			$subBlocks = preg_split( $blockExp, $content );
			$parentBlocks = preg_split( $blockExp, $parentContent );
			// 内容一般都比较大，分割完就没用了，先清掉
			unset( $content, $parentContent );
			
			// 遍历子模板的块
			for( $index = 0, $len = count( $subBlocks ); $index < $len; $index = $nextIndex ) {
				$nextIndex = $index + 1;
				$match = array();
				if( ! preg_match( "/^{$b}block\\s+(\\w+){$e}/i", $subBlocks[ $index ], $match ) ) {
					continue;
				}
				// 把起始的块添加到新的数组中
				$blocks = array( $subBlocks[ $index ] );
				// 一直往下找到结束标签
				$layer = 1;
				while( preg_match( "/^{$b}block\\s+\\w+{$e}/i", $subBlocks[ $nextIndex ] )  ? ++$layer : --$layer ) {
					$blocks[] = $subBlocks[ $nextIndex ];
					$nextIndex++;
				}
				list( $startOffset, $endOffset ) = $this->_findExtendBlockOffset( $match[1], $parentBlocks );
				// 如果存在，则替换
				if( $startOffset !== false && $endOffset !== false ) {
					array_splice( $parentBlocks, $startOffset, ( $endOffset - $startOffset ), $blocks );
				}
			}
			$content = implode( '', $parentBlocks );
		}
		return $content;
	}
	
	private function _findExtendBlockOffset( $blockName, &$parentBlocks ) {
		$b = $this->_beginTag;
		$e = $this->_endTag;
		// 在父模板中找出同名的块
		$startOffset = $endOffset = false;
		$layer = 1;
		foreach( $parentBlocks as $offset => $value ) {
			if( $startOffset === false && preg_match( "/^{$b}block\\s+{$blockName}{$e}/i", $value ) ) {
				$startOffset = $offset;
			} elseif( $startOffset !== false ) {
				$layer = preg_match( "/^{$b}block\\s+\\w+{$e}/i", $value ) ? ++$layer : --$layer;
			}
			if( $layer === 0 ) {
				$endOffset = $offset;
				break;
			}
		}
		return array( $startOffset, $endOffset );
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
		$exp = "/{$this->_beginTag}tag:(\\w+)(?:{$this->_endTag}|(\\s+.*?){$this->_endTag})/i";
		$tags = $this->_tags;
		$content = preg_replace_callback( $exp, function( $match ) use( $tags ) {
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
				preg_match_all( '/(\w+)\s*=\s*+(?:(([\'"]).+?\3)|([^\'"][^\s]+))/i', $match[2], $matchArgs );
				if( ! empty( $matchArgs[1] ) ) {
					$args = array();
					// 取到这些参数后，因为值有两种类型
					// 所以如果不在第一种类型中，则去另一个类型里取
					foreach( $matchArgs[1] as $index => $name ) {
						$args[ $name ] = ( ! empty( $matchArgs[4][ $index ] ) ?
							$matchArgs[4][ $index ] : $matchArgs[2][ $index ] );
					}
					// 校对参数所在的位置，生成相应的'传入参数'的字符串
					foreach( $tags[ $tagName ]['args'] as $argName => $defaultValue ) {
						$value = ( isset( $args[ $argName ] ) ? $args[ $argName ] : $defaultValue );
						// 允许参数值使用变量，这样会很灵活，而且在嵌套标签里很有用
						if( preg_match( '/^("?)\$.*\1$/i', $value ) ) {
							$exportArgs .= trim( $value, '"' ) . ',';
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
	
	public function fetch() {
		
	}
	
	public function display() {
		$file = \Melon::env('root') . '/Melon/Data/complie.php';
		$this->_content = $this->compile( $this->_template );
		file_put_contents($file, $this->_content);
		include $file;
	}
}
