<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Util;

use Melon\Exception;
defined('IN_MELON') or die('Permission denied');

/**
 * 模板视图
 * 
 * <pre>
 * 模板是通过定义一系统标签，使用正则表达式替换为标准的PHP语法的格式
 * 可使用的标签如下（以标签符是{}为例子）：
 * {$var} 注：输出一个变量，可使用assign或assignItem方法注入这些变量
 * {if 条件} 内容 {/if}
 * {if 条件} 内容 {else} 内容 {/if}
 * {if 条件} 内容 {elseif 条件} 内容 {/if}
 * {foreach $arr $value} 内容 {/foreach}
 * {foreach $arr $key $value} 内容 {/foreach}
 * {print 变量或函数}  注：可以使用print标签对内容进行处理，比如 {print date( 'Y-m-d', $time )}
 * {php php代码/}
 * {php} php代码 {/php}
 * {include 子模板路径}  注：可在模板中引入子模板
 * 
 * {extend 继承模板路径/}
 * {block 块名称} 块内容 {/block}
 * 如果你熟悉smarty中的继承，应该不难理解，使用方法基本类似
 * 继承标签由extend和block标签共同完成
 * 继承模板中的block会覆盖父模板中的同名block内容
 * 如果没有覆盖（同名块）父模板某个block，则使用这个block中默认的内容
 * 
 * {tag:标签名 属性=值} 内容 {/tag}  注：可使用assignTag或assignTagItem方法添加自定义标签
 * 你可以在模板中使用这个自定义标签
 * // 声明一个获取某个列表数据的函数
 * function getList( $id, $limit ) {
 *        // 返回一个列表数据
 * }
 * // 定义一个list标签
 * $template->assignTag( 'list', array(
 *        'callable' => 'getList',// callable可以是符合php is_callable函数规定的类型
 *        'args' => array( 'id' => 1, 'limit' => 10 )
 * ) );
 * 
 * 
 * 如果getList返回一个数组，在模板中就可以这样使用，程序会自动遍历这个数组：
 * {tag:list id=1}
 *        {$data} //$data是getList返回的数组中的每个元素的值
 * {/tag:list}
 * 参数可使用变量，同时也可以自定义遍历的元素值的名称：
 * {tag:list id=$id result=row}
 *        {$row}
 * {/tag:list}
 * 
 * 如果getList返回一个字符串，在模板中可以这样使用，程序会输出这个字符串：
 * {tag:list id=1 /}
 * 
 * 另外，标签也可以互相嵌套，没有限制
 * </pre>
 * 
 * 在引入编译文件时，会自动注入一个$__melonTemplate变量，指向当前模板对象的实例
 * 程序会使用这个实例进行一些操作，比如检查更新等
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 */
class Template {
    
    /**
     *  模板路径
     *  @var string
     */
    protected $_template;
    
    /**
     *  模板目录
     *  @var string
     */
    protected $_templateDir;
    
    /**
     * 模板的编译文件保存路径
     * @var string
     */
    protected $_compileDir;
    
    /**
     * 注入的变量
     * @var array 
     */
    protected $_vars = array();
    
    /**
     * 注入的自定义标签
     * @var array
     */
    protected $_tags = array();
    
    /**
     * 模板标签起始符
     * @var string
     */
    protected $_beginTag;
    
    /**
     * 模板标签结束符
     * @var string
     */
    protected $_endTag;
    
    /**
     * 
     * @param string $template 模板路径
     * @param array $tag 模板标签符 第一个元素表示标签起始符，第二个元素表示标签结束符
     */
    public function __construct( $tag = array( '{', '}' ) ) {
        $this->_beginTag = $tag[0];
        $this->_endTag = $tag[1];
    }
    
    /**
     * 注入一个变量
     * 
     * @param string $key 变量名
     * @param mixed $value 值
     * @return \Melon\Base\Template
     */
    public function assign( $key, $value ) {
        $this->_vars[ $key ] = $value;
        return $this;
    }
    
    /**
     * 注入一组变量
     * 
     * @param array $vars 变量组，每个元素都表示一个变量
     * @return \Melon\Base\Template
     */
    public function assignItem( array $vars ) {
        foreach( $vars as $key => $value ) {
            $this->_vars[ $key ] = $value;
        }
        return $this;
    }
    
    /**
     * 注入一个自定义标签
     * 
     * @param string $tagname 自定义标签名
     * @param array $setting 标签设置
     * 要提供的参数：
     * 1. callable    string    可直接调用的函数，符合php is_callable函数规定的类型即可
     * 2. args        array     参数数组，key是参数名称，value是默认值，数组元素必须按照callable函数的参数顺序一一对应
     * @return \Melon\Base\Template
     */
    public function assignTag( $tagname, $setting ) {
        $this->_tags[ $tagname ] = $setting;
        return $this;
    }
    
    /**
     * 注入一组自定义标签
     * 
     * @param array $tags 标签组，每个元素都表示一个自定义标签
     * @return \Melon\Base\Template
     */
    public function assignTagItem( array $tags ) {
        foreach( $tags as $key => $value ) {
            $this->_vars[ $key ] = $value;
        }
        return $this;
    }
    
    /**
     * 设置模板目录
     * 
     * @param string $templateDir 路径
     * @return \Melon\Base\Template
     */
    public function setTemplateDir( $templateDir ) {
        $this->_templateDir = $templateDir;
        return $this;
    }
    
    /**
     * 设置编译文件目录
     * 
     * @param string $compileDir 路径
     * @return \Melon\Base\Template
     */
    public function setCompileDir( $compileDir ) {
        $this->_compileDir = $compileDir;
        return $this;
    }
    
    /**
     * 获取子模板的编译文件
     * 
     * @param string $dir 子模板所在的目录，因为$template可能是通过变量动态解释得到的，所以需要这个参照目录计算真正的路径
     * @param string $template 模板路径，可以为绝对路径，不过程序优先取相对于$dir目录下的模板
     * @return string 编译后的文件路径
     */
    public function getSubTemplate( $dir, $template ) {
        if( file_exists( $dir . DIRECTORY_SEPARATOR . $template ) ) {
            $template = $dir . DIRECTORY_SEPARATOR . $template;
        }
        return $this->_createCompileFile( $template );
    }
    
    /**
     * 检查模板更新
     * 
     * @param string $checkTemplate 要检查的模板
     * @param string $compareTime 比较时间，将与被检查的模板的修改时间做对比
     * @param string $updateTemplate 更新哪个模板，默认是被检查的模板
     */
    public function checkTemplateChange( $checkTemplate, $compareTime, $updateTemplate = null ) {
        if( is_null( $updateTemplate ) ) {
            $updateTemplate = $checkTemplate;
        }
        if( ! file_exists( $checkTemplate ) || filemtime( $checkTemplate ) > $compareTime ) {
            $this->_createCompileFile( $updateTemplate, true );
        }
    }
    
    /**
     * 创建模板的编译文件
     * 如果模板有更新，编译文件才会被更新，否则返回已经编译的那个
     * 
     * @param string $template 模板路径
     * @param boolean $forceUpdate 强制更新编译文件
     * @return string 编译后的文件路径
     * @throws \Melon\Exception\RuntimeException
     */
    protected function _createCompileFile( $template, $forceUpdate = false ) {
        $targetDir = $this->_compileDir;
        if( ! $targetDir ||
            ( ! is_dir( $targetDir ) && ! mkdir( $targetDir, 0777, true ) ) ) {
            \Melon::throwException( "模板编译目录不存在" );
        }
        $_targetDir = realpath( $targetDir );
        $_sourceFile = realpath( $template );
        if( ! $_sourceFile ) {
            \Melon::throwException( "模板文件{$template}不存在" );
        }
        $compileFile = $_targetDir . DIRECTORY_SEPARATOR . md5( $_sourceFile ) . '.php';
        if( $forceUpdate || ! file_exists( $compileFile ) ||  filemtime( $compileFile ) < filemtime( $template ) ) {
            $content = $this->_compile( $template );
            file_put_contents( $compileFile , $content );
        }
        return $compileFile;
    }
    
    /**
     * 获取模板内容
     * 
     * @param string $template 模板路径
     * @return string 模板内容
     * @throws \Melon\Exception\RuntimeException
     */
    protected function _getContent( $template ) {
        if( ! file_exists( $template ) ) {
            \Melon::throwException( "模板文件{$template}不存在" );
        }
        return file_get_contents( $template );
    }
    
    /**
     * 清除标签两边的html注释符
     * 
     * @param string &$content
     * @return string
     */
    protected function _cleanNote( $content ) {
        return preg_replace( "/\<\!\-\-{$this->_beginTag}(.+?){$this->_endTag}\-\-\>/", "{$this->_beginTag}\$1{$this->_endTag}", $content );
    }

    /**
     * 编译模板
     * 
     * @param string $template 模板文件路径
     * @return string 编译后的内容
     */
    protected function _compile( $template ) {
        $content = $this->_getContent( $template );
        
        $content = $this->_cleanNote( $content );
        // 处理几个比较麻烦的标签
        // 先把内容继承过来
        $content = $this->_compileExtend( dirname( $template ), $content );
        // 引入子模板
        $content = $this->_compileInclude( dirname( $template ), $content );
        // 自定义标签
        $content = $this->_compileTag( $content );
        
        // 还有一些简单好用的标签
        $exp = "/{$this->_beginTag}(\/?[^\n\r]+?){$this->_endTag}/";
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
        
        // 为没有引号的数组索引添加引号
        // 这个要放到最后，因为一开始添加引号可能会与标签中的引号冲突
        $indexQuote = '/((\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)/';
        $content = preg_replace_callback( $indexQuote, function( $match ) {
            return str_replace( '\"', '"', preg_replace( '/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s', '[\'$1\']', $match[1] ) );
        }, $content );
        
        return $content;
    }
    
    /**
     * 编译继承标签
     * 该方法会把block标签清空
     * 
     * @param string $dir 参照目录（$content所指向的目录）
     * @param string $content 模板内容
     * @return string 编译后的内容
     */
    protected function _compileExtend( $dir, &$content ) {
        $extendContent = $this->_compileExtendSnippet( $dir, $content );
        // 把标签清掉
        return preg_replace( "/{$this->_beginTag}(block\s+\w+|\/block){$this->_endTag}/i", '', $extendContent );
    }
    
    /**
     * 编译一段继承标签
     * 该方法的特点是不会清空block标签名，仅把内容继承过来
     * 
     * 继承标签可以互相嵌套，由于这个原因，我使用了类似平衡组的方法
     * 把block的头和尾分割为各自的一小块，按照层级关系抽取和替换
     * 可能有点复杂，不过我曾经试过用正则表达式处理，非常简洁
     * 悲剧的是性能太差，超过200行代码左右的模板就会处理失败
     * 如果你有更高效的处理方式，请联系我，感谢
     * 
     * 
     * @param string $dir 参照目录（$content所指向的目录）
     * @param string $content 模板内容
     * @return string 编译后的内容
     */
    protected function _compileExtendSnippet( $dir, &$content ) {
        $b = $this->_beginTag;
        $e = $this->_endTag;
        // 检查是否有声明继承
        $exp = "/^[\\s\\t\\r\\n]*{$b}extend\\s+(['\"]?)(.*?)(\\1)\\s*\\/?{$e}/is";
        $match = array();
        $content = $this->_cleanNote( $content );
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
            // 把标签的头和尾分割为小块
            $blockExp = "/(?={$b}(block\\s+\\w+|\/block){$e})/i";
            $subBlocks = preg_split( $blockExp, $content );
            $parentBlocks = preg_split( $blockExp, $parentContent );
            // 内容一般都比较大，分割完就没用了，先清掉
            unset( $content, $parentContent );
            
            // 遍历子模板的块
            for( $index = 0, $len = count( $subBlocks ); $index < $len; $index = $nextIndex ) {
                $nextIndex = $index + 1;
                $match = array();
                // 一个一个块分别处理
                // 如果不是块的起始标签就跳过
                if( ! preg_match( "/^{$b}block\\s+(\\w+){$e}/i", $subBlocks[ $index ], $match ) ) {
                    continue;
                }
                // 把起始的块添加到新的数组中
                $blocks = array( $subBlocks[ $index ] );
                // 一直往下找到结束标签
                // 并且把结束标签之前的内容都添加进来
                $layer = 1;
                while( preg_match( "/^{$b}block\\s+\\w+{$e}/i", $subBlocks[ $nextIndex ] )  ? ++$layer : --$layer ) {
                    $blocks[] = $subBlocks[ $nextIndex ];
                    $nextIndex++;
                }
                // 在父模板中找到对应标签的块进行替换
                list( $startOffset, $endOffset ) = $this->_findExtendBlockOffset( $match[1], $parentBlocks );
                // 如果存在，则替换
                if( $startOffset !== false && $endOffset !== false ) {
                    array_splice( $parentBlocks, $startOffset, ( $endOffset - $startOffset ), $blocks );
                }
            }
            // 把内容整合起来
            $mtime = filemtime( $template );
            $content = "<?php \$__melonTemplate->checkTemplateChange( '{$template}', '{$mtime}', '{$this->_template}' ); ?>";
            $content .= implode( '', $parentBlocks );
        }
        return $content;
    }
    
    /**
     * 找到某个块的开始和结束位置
     * 
     * @param string $blockName 块名称
     * @param array $parentBlocks 块组
     * @return array array( 开始位置, 结束位置 )
     */
    protected function _findExtendBlockOffset( $blockName, &$parentBlocks ) {
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
     * 编译include标签
     * 
     * 使用include标签可以引入一个子模板，就像PHP的include一样
     * 
     * @param string $dir 需要指定当前模板的目录，这样include可以处理相对路径
     * @param string &$content 模板内容
     * @return string
     */
    protected function _compileInclude( $dir, &$content ) {
        $self = $this;
        $exp = "/{$this->_beginTag}include\\s+((['\"]?).*?(\\2))\\s*\\/?{$this->_endTag}/i";
        return preg_replace_callback( $exp, function( $match ) use( $self, $dir ) {
            $template = $match[1];
            if( ! preg_match( '/^([\'"]).*\1$/', $template ) ) {
                $template = "'$template'";
            }
            // 使得include在双引号中支持花括号引用变量
            // 因为{$var}可能会被当作变量标签替换为php语法，在被替换之前首先把这个处理了
            // 否则会有语法错误
            // 好吧，这方法很hack，也不够灵活，不过我暂时只能想到这方法了
            // 例："/www/$arr['dir']/file.html"
            // 替换后："/www/" . $arr['dir'] . "/file.html"
            elseif( preg_match( '/^(["]).*\1$/', $template ) ) {
                $template = preg_replace( '/\{(\$.*?)\}/', '".$1."', $template );
            }
            // 编译子模板
            return "<?php include \$__melonTemplate->getSubTemplate( '{$dir}', {$template} ); ?>";
        }, $content );
    }
    
    /**
     * 编译自定义标签
     * 
     * 自定义标签是一个可扩展的功能，可以方便制定一些像CMS模板标签的功能
     * 
     * @param string &$content
     * @return string
     * @throws \Melon\Exception\RuntimeException
     */
    protected function _compileTag( &$content ) {
        $exp = "/{$this->_beginTag}tag:(\\w+)(?:{$this->_endTag}|(\\s+.*?){$this->_endTag})/i";
        $tags = $this->_tags;
        $content = preg_replace_callback( $exp, function( $match ) use( $tags ) {
            // 标签名
            $tagName = $match[1];
            if( ! isset( $tags[ $tagName ] ) ) {
                \Melon::throwException( "没有定义{$tagName}模板标签" );
            }
            if( ! is_callable( $tags[ $tagName ]['callable'] ) ) {
                \Melon::throwException( "模板标签{$tagName}的回调函数不可调用" );
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
                preg_match_all( '/(\w+)\s*=\s*+(?:(([\'"]).+?\3)|([^\'"][^\s]*))/i', $match[2], $matchArgs );
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
                        if( preg_match( '/^("?)\$.*\1$/', $value ) ) {
                            $exportArgs .= trim( $value, '"' ) . ',';
                        }
                        elseif( preg_match( '/^([\'"]).*\1$/', $value ) ) {
                            $exportArgs .= $value . ',';
                        // 要注意参数值可能里包含了引号，这样就会产生意外的语法错误
                        // 我使用单引号标注参数值，所以只转义单引号就行
                        } else {
                            $exportArgs .= '\'' . addcslashes( $value, '\'' ) . '\',';
                        }
                    }
                    // 别忘记去掉右边的逗号
                    $exportArgs = ',' . rtrim( $exportArgs, ',' );
                    // 标签可以通过'result'参数自定义返回结果的变量名
                    $resultName = ( isset( $args['result'] ) ? $args['result'] : $resultName );
                }
            }
            // 单标签的话，直接输出执行结果
            if( substr( $match[0], -2, 1 ) === '/' ) {
                return "<?php echo \$__melonTemplate->callTag('{$tagName}'{$exportArgs}); ?>";
            }
            // 否则就用遍历了
            return "<?php foreach(\$__melonTemplate->callTag('{$tagName}'{$exportArgs}) as \${$resultName}) { ?>";
        }, $content );
        
        // 处理结束标签
        $content = preg_replace( "/{$this->_beginTag}\\/tag:(\\w+){$this->_endTag}/i", '<?php } ?>', $content );
        // over
        return $content;
    }
    
    /**
     * 调用一个注入到模板的标签的回调函数，并返回其执行结果
     * 
     * @param string $tagName 注入的标签名
     * @param mixed $arg1 回调函数的第一个参数
     * @param mixed $_ 回调函数更多参数
     * @return mixed 回调函数执行结果
     */
    public function callTag( $tagName = '', $arg1 = null, $_ = null ) {
        if( ! isset( $this->_tags[ $tagName ] ) ) {
            \Melon::throwException( "没有定义{$tagName}模板标签" );
        }
        $args = func_get_args();
        array_shift( $args );
        return call_user_func_array( $this->_tags[ $tagName ]['callable'], $args );
    }
    
    /**
     * 把模板运行结果返回
     * 
     * @param string $template 模板路径，如果设置了模板目录，则它是相对于模板目录下的文件路径
     * @return string
     */
    public function fetch( $template ) {
        ob_start();
        if( $this->_templateDir ) {
            $this->_template = $this->_templateDir . DIRECTORY_SEPARATOR . $template;
        } else {
            $this->_template = $template;
        }
        $this->_show();
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }
    
    /**
     * 把模板运行结果输出
     * 
     * @param string $template 模板路径，如果设置了模板目录，则它是相对于模板目录下的文件路径
     * @return void
     */
    public function display( $template ) {
        if( $this->_templateDir ) {
            $this->_template = $this->_templateDir . DIRECTORY_SEPARATOR . $template;
        } else {
            $this->_template = $template;
        }
        $this->_show();
    }
    
    /**
     * 显示编译文件
     * 
     * @return void
     */
    protected function _show() {
        // 得到编译文件
        $compileFile = $this->_createCompileFile( $this->_template );
        // 导入变量
        extract( $this->_vars );
        // 导入当前模板对象
        $__melonTemplate = $this;
        include $compileFile;
    }
}
