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
	
	private function _getContent() {
		if( ! file_exists( $this->_template ) ) {
			throw new \Melon\Exception\RuntimeException( "模板文件{$this->_template}不存在" );
		}
		return file_get_contents( $this->_template );
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
	
	public function compile() {
		$b = $this->_beginTag;
		$e = $this->_endTag;
		$this->_content = $this->_getContent();
		$this->_replaceVars();
		$this->_replaceTag();
		$exps = array(
			"/{$b}php\\s+(.*?)\\/?{$e}/is"		=> '$1;',
			"/{$b}print\\s+(.*?)\\/?{$e}/i"		=> 'echo $1;',
			"/{$b}if\\s+(.*?){$e}/i"			=> 'if($1){',
			"/{$b}else{$e}/i"					=> '}else{',
			"/{$b}elseif\\s+(.*?){$e}/i"			=> '}elseif($1){',
			"/{$b}foreach\\s+(.*?)(?<!=){$e}/i"	=> 'foreach($1){',
			"/{$b}\\/(if|foreach){$e}/i"			=> '}',
		);
		foreach( $exps as $exp => $replace ) {
			$this->_content = preg_replace( $exp, "<?php {$replace} ?>", $this->_content );
		}
		
		$file = \Melon::env('root') . '/Melon/Data/complie.php';
		file_put_contents($file, $this->_content);
		
		include $file;
	}
	
	private function _replaceVars() {
		$self = $this;
		$b = $this->_beginTag;
		$e = $this->_endTag;
		$this->_content = preg_replace_callback( "/{$b}((?:print|if|elseif|foreach|\\$).*?){$e}/i",
			function( $match ) use( $self, $b, $e ) {
			$var = $self->replaceVar( $match[1] );
			if( $match[1][0] === '$' ) {
				return "<?php echo {$var}; ?>";
			}
			return $b . $var. $e;
		}, $this->_content );
	}
	
	public function replaceVar( $var ) {
		$count = 0;
		// 嵌套变量
		$exp = array( "/\\[\s*([a-zA-Z_]+)\s*\\]/", "/\\.([a-zA-Z_]+)(?![^\\[]*['\"]\\s*\\])/" );
		$var = preg_replace( $exp, '[\'$1\']', $var, -1, $count );
		return $var;
	}
	
	private function _replaceTag() {
		$tags = $this->_tags;
		$exp = "/{$this->_beginTag}tag:(\\w+)(?:{$this->_endTag}|(\\s+.*?){$this->_endTag})/i";
		$self = $this;
		$match = array();
		$this->_content = preg_replace_callback( $exp, function( $match ) use( $self, $tags ) {
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
						$args[ $name ] = ! empty( $matchArgs[4][ $index ] ) ?  $matchArgs[4][ $index ] : $matchArgs[3][ $index ];
					}
					foreach( $tags[ $tagName ]['args'] as $argName => $defaultValue ) {
						$value = isset( $args[ $argName ] ) ? $args[ $argName ] : $defaultValue;
						if( substr( $value, 0, 1 ) === '$' ) {
							$exportArgs .= $self->replaceVar( $value ) . ',';
						} else {
							$exportArgs .= "'{$value}',";
						}
					}
					$exportArgs =  "array( $exportArgs )";
					$resultName = isset( $args['result'] ) ? $args['result'] : $resultName;
				}
			}
			if( substr( $match[0], -2, 1 ) === '/' ) {
				return "<?php echo call_user_func_array( '{$tags[ $tagName ]['callable']}', {$exportArgs} ); ?>";
			}
			return "<?php foreach(call_user_func_array( '{$tags[ $tagName ]['callable']}', {$exportArgs} ) as \${$resultName}) { ?>";
		}, $this->_content );
		$this->_content = preg_replace( "/{$this->_beginTag}\\/tag:(\\w+){$this->_endTag}/i", '<?php } ?>', $this->_content);
	}
	
	public function fetch() {
		
	}
	
	public function display() {
		
	}
}
