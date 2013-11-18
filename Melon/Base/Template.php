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
	
	public function compile( $template ) {
		$content = $this->_getContent( $template );
		$content = $this->_replaceInclude( $template, $content );
		
		$content = $this->_replaceVars( $content );
		$content = $this->_replaceTag( $content );
		$b = $this->_beginTag;
		$e = $this->_endTag;
		$exps = array(
			"/{$b}php\\s+(.*?)\\/?{$e}/is" => '$1;',
			"/{$b}print\\s+(.*?)\\/?{$e}/i" => 'echo $1;',
			"/{$b}if\\s+(.*?){$e}/i" => 'if($1){',
			"/{$b}else{$e}/i" => '}else{',
			"/{$b}elseif\\s+(.*?){$e}/i" => '}elseif($1){',
			"/{$b}foreach\\s+([^\\s]+)\\s*([^\\s]+)\\s*{$e}/i" => 'foreach($1 as $2){',
			"/{$b}foreach\\s+([^\\s]+)\\s*([^\\s]+)\\s*([^\\s]+)\\s*{$e}/i" => 'foreach($1 as $2 => $3){',
			"/{$b}\\/(if|foreach){$e}/i" => '}',
		);
		foreach( $exps as $exp => $replace ) {
			$content = preg_replace( $exp, "<?php {$replace} ?>", $content );
		}
		
		$this->_includeFiles[] = $template;
		return $content;
	}
	
	private function _replaceVars( $content ) {
		$self = $this;
		$b = $this->_beginTag;
		$e = $this->_endTag;
		return preg_replace_callback( "/{$b}((?:print|if|elseif|foreach|\\$).*?){$e}/i",
			function( $match ) use( $self, $b, $e ) {
			$var = $self->replaceVar( $match[1] );
			if( $match[1][0] === '$' ) {
				return "<?php echo {$var}; ?>";
			}
			return $b . $var. $e;
		}, $content );
	}
	
	public function replaceVar( $var ) {
		$count = 0;
		// 嵌套变量
		$exp = array( "/\\[\s*([a-zA-Z_]+)\s*\\]/", "/\\.([a-zA-Z_]+)(?![^\\[]*['\"]\\s*\\])/" );
		$var = preg_replace( $exp, '[\'$1\']', $var, -1, $count );
		return $var;
	}
	
	private function _replaceTag( $content ) {
		$tags = $this->_tags;
		$exp = "/{$this->_beginTag}tag:(\\w+)(?:{$this->_endTag}|(\\s+.*?){$this->_endTag})/i";
		$self = $this;
		$match = array();
		$content = preg_replace_callback( $exp, function( $match ) use( $self, $tags ) {
			$tagName = $match[1];
			if( ! isset( $tags[ $tagName ] ) ) {
				throw new \Melon\Exception\RuntimeException( "没有定义{$tagName}模板标签" );
			}
			$exportArgs = '';
			$resultName = 'data';
			if( isset( $match[2] ) ) {
				$matchArgs = array();
				preg_match_all( "/(\\w+)\\s*=\\s*+(?:(['\"])(.+?)\\2|([^'\"][^\\s]+))/", $match[2], $matchArgs );
				if( ! empty( $matchArgs[1] ) ) {
					$args = array();
					foreach( $matchArgs[1] as $index => $name ) {
						$args[ $name ] = ( ! empty( $matchArgs[4][ $index ] ) ?
							$matchArgs[4][ $index ] : $matchArgs[3][ $index ] );
					}
					foreach( $tags[ $tagName ]['args'] as $argName => $defaultValue ) {
						$value = ( isset( $args[ $argName ] ) ? $args[ $argName ] : $defaultValue );
						if( substr( $value, 0, 1 ) === '$' ) {
							$exportArgs .= $self->replaceVar( $value ) . ',';
						} else {
							$exportArgs .= '\'' . addcslashes( $value, '\'' ) . '\',';
						}
					}
					$exportArgs = rtrim( $exportArgs, ',' );
					$resultName = ( isset( $args['result'] ) ? $args['result'] : $resultName );
				}
			}
			if( substr( $match[0], -2, 1 ) === '/' ) {
				return "<?php echo {$tags[ $tagName ]['callable']}({$exportArgs}); ?>";
			}
			return "<?php foreach({$tags[ $tagName ]['callable']}({$exportArgs}) as \${$resultName}) { ?>";
		}, $content );
		$content = preg_replace( "/{$this->_beginTag}\\/tag:(\\w+){$this->_endTag}/i", '<?php } ?>', $content );
		
		return $content;
	}
	
	public function _replaceInclude( $template, $content ) {
		$sourceTemplate = $this->_template;
		$self = $this;
		return preg_replace_callback( "/{$this->_beginTag}include\\s+(['\"]?)(.*?)(\\1)\\s*\\/?{$this->_endTag}/i",
			function( $match ) use( $self, $template ) {
			if( \Melon\Base\Func\isAbsolutePath( $match[2] ) ) {
				$includeTemplate = $match[2];
			} else {
				$includeTemplate = dirname( $template ) . DIRECTORY_SEPARATOR . $match[2];
			}
			if( ! file_exists( $includeTemplate ) ) {
				throw new \Melon\Exception\RuntimeException( "解释{$template}模板时，子模板{$match[2]}不存在" );
			}
			return $self->compile( $includeTemplate );
		}, $content );
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
