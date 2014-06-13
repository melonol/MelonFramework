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

defined('IN_MELON') or die('Permission denied');

/**
 * 一个友好的整理和输出调试或错误信息工具
 * 
 * 它可以解释调用方法栈，以及根据PHP的API类型自动判断输出哪种格式的数据
 * 比如说服务器调用时会输出HTML，命令行调用时输出普通文本
 * 当输出HTML时，甚至可以显示每个被调用方法所在的文件位置的上下文片段，称为代码片段更好点
 * 前提是你要提供足够的信息
 * 
 * @package Melon
 * @since 0.3.0
 * @author Melon
 */
class DebugMessage {
    
    // 自动判断输出格式
    const DISPLAY_AUTO = 0;
    // 输出HTML
    const DISPLAY_HTML = 1;
    // 输出文本
    const DISPLAY_TEXT = 2;
    
    /**
     * 消息类型
     * 
     * @var string
     */
    protected $_type;
    
    /**
     * 消息
     * 
     * 它是一个整个事件的主要描述
     * @var mixed
     */
    protected $_message;
    
    /**
     * 格式化后的消息
     * 
     * @var string
     */
    protected $_messageFormat;
    
    /**
     * 相关的脚本
     * 
     * 消息所描述的事件发生在哪个脚本
     * @var string 
     */
    protected $_file;
    
    /**
     * 所在的行
     * 
     * 消息所描述的事件发生在脚本中的那一行
     * @var int
     */
    protected $_line;
    
    /**
     * 调用方法栈
     * 
     * 这个是使用debug_backtrace方法、捕获异常等方式得到的栈
     * @var array
     */
    protected $_trace;
    
    /**
     * 是否显示方法栈
     * 
     * @var boolean 
     */
    protected $_showTrace = true;
    
    /**
     * 构造函数
     * 
     * @param string $type 消息类型，目前用来显示给用户看的类型，以后可能还有其它作用
     * @param mixed $message 消息，它是一个整个事件的主要描述
     * @param string $file [可选] 脚本，消息所描述的事件发生在哪个脚本
     * @param int $line [可选] 所在的行，消息所描述的事件发生在脚本中的那一行
     * @param array $trace [可选] 调用方法栈，这个是使用debug_backtrace方法、捕获异常等方式得到的栈
     */
    public function __construct( $type, $message, $file = null, $line = null, $trace = null ) {
        $this->_type = $type;
        $this->_message = $message;
        $this->_file = $file;
        $this->_line = $line;
        $this->_trace = $trace;
    }
    
    /**
     * 输出整理后的消息
     * 
     * @param enum $showType [可选] 输出格式：
     * 1. DebugMessage::SHOW_AUTO    [默认] 自动判断
     * 2. DebugMessage::SHOW_TEXT    文本 
     * 3. DebugMessage::SHOW_HTML    HTML
     * @param boolean $showTrace [可选] 是否显示trace
     * @param boolean $showCodeSnippet [可选] 如果输出HTML（输出文本该选项无效），是否输出消息所在位置的代码片段
     * @return void
     */
    public function show( $showType = self::DISPLAY_AUTO, $showTrace = true, $showCodeSnippet = true ) {
        echo $this->parse( $showType, $showTrace, $showCodeSnippet ) . "\r\n";
    }
    
    /**
     * 如果不想输出，可以获取这些整理过的消息
     * 
     * @param enum $showType [可选] 输出格式：
     * 1. DebugMessage::SHOW_AUTO    [默认] 自动判断
     * 2. DebugMessage::SHOW_TEXT    文本 
     * 3. DebugMessage::SHOW_HTML    HTML
     * @param boolean $showTrace [可选] 是否显示trace
     * @param boolean $showCodeSnippet [可选] 如果输出HTML（输出文本该选项无效），是否输出消息所在位置的代码片段
     * @return string
     */
    public function parse( $showType = self::DISPLAY_AUTO, $showTrace = true, $showCodeSnippet = true ) {
        $this->_showTrace = !! $showTrace;
        if( $showType === self::DISPLAY_AUTO ) {
            $_showType = ( \Melon::env( 'clientType' ) === 'browser' ? self::DISPLAY_HTML : self::DISPLAY_TEXT );
        } else {
            $_showType = ( $showType === self::DISPLAY_HTML ? self::DISPLAY_HTML : self::DISPLAY_TEXT );
        }
        if( $_showType === self::DISPLAY_TEXT ) {
            $this->_messageFormatText();
            return $this->_setText();
        } else {
            $this->_messageFormatHtml();
            return $this->_setHtml( $showCodeSnippet );
        }
    }
    
    /**
     * 根据message的数据类型格式化为普通文本
     * 字符串使用htmlspecialchars转义，数组使用print_r，对象使用var_dump
     */
    protected function _messageFormatText() {
        if( is_array( $this->_message ) ) {
            $this->_messageFormat = "\r\n" . print_r( $this->_message, true );
        } else if( is_object( $this->_message ) ) {
            ob_start();
            var_dump( $this->_message );
            $this->_messageFormat = "\r\n" . ob_get_contents();
            ob_clean();
        } else {
            $this->_messageFormat = strval( $this->_message );
        }
    }
    
    /**
     * 根据message的数据类型格式化为HTML
     * 字符串使用htmlspecialchars转义，数组使用print_r，对象使用var_dump
     */
    protected function _messageFormatHtml() {
        if( is_array( $this->_message ) ) {
            $this->_messageFormat = '<pre>' . print_r( $this->_message, true ) . '</pre>';
        } else if( is_object( $this->_message ) ) {
            ob_start();
            var_dump( $this->_message );
            $this->_messageFormat = '<pre>' . ob_get_contents() . '</pre>';
            ob_clean();
        } else {
            $this->_messageFormat = nl2br( htmlspecialchars( strval( $this->_message ) ) );
        }
    }
    
    /**
     * 设置为HTML数据
     * 
     * 它将数据放到一个TABLE里，消息主体是第一行
     * 其它行则是按调用方法栈（如果有提供的话）的顺序依次输出
     * 
     * @param boolean $showCodeSnippet [可选] 如果输出HTML（输出文本该选项无效），是否输出消息所在位置的代码片段
     * @return string
     */
    protected function _setHtml( $showCodeSnippet = true ) {
        $table = '<table style="margin: 10px; padding: 5px; border-collapse: collapse; background-color: #eeefff; color: #000;">';
        list( $file, $line ) = $this->_replaceEval( $this->_file, $this->_line );
        $table .= $this->_setTr( 'th', '', $file, $line, $showCodeSnippet );

        if( $this->_showTrace && is_array( $this->_trace ) && ! empty( $this->_trace ) ) {
            foreach( $this->_trace as $info ) {
                if( ! isset( $info['function'] ) ) {
                    continue;
                }
                $func = $info['function'];
                if( isset( $info['class'] ) ) {
                    $func = $info['class'] . $info['type'] . $func;
                }
                if( isset( $info['file'] ) ) {
                    list( $file, $line ) = $this->_replaceEval( $info['file'], $info['line'] );
                    $table .= $this->_setTr( 'td', $func, $file, $line, $showCodeSnippet );
                } else {
                    $table .= $this->_setTr( 'td', $func );
                }
            }
        }
        return ( $table . '</table>' );
    }
    
    /**
     * 设置一行表格数据
     * 
     * @param enum $elem th|td 行内元素，如果是th则表示它是当前消息的主体，一个表格只应该有一个th，其它都是td
     * @param string $func 被调用的方法名，只是用来显示，不过最好规范一点
     * @param string $file 方法被调用时所在的文件
     * @param int $line 方法被调用时所在的文件行
     * @param boolean $showCodeSnippet [可选] 如果输出HTML（输出文本该选项无效），是否根据file和line查找并输出消息所在位置的代码片段
     * @return string
     */
    protected function _setTr( $elem, $func, $file = null, $line = null, $showCodeSnippet = true ) {
        $tr = '<tr>';
        if( ! is_null( $file ) && file_exists( $file ) && ! is_null( $line ) ) {
            if( $elem === 'th' ) {
                $title = "{$this->_type}: {$this->_messageFormat} in {$file}({$line})";
            } else {
                $title = "{$file}({$line}) --> {$func}";
            }
            $tr .= "<{$elem} style=\"border: 1px solid #000; padding: 5px; text-align: left;" . (
                    $elem === 'th' ? " font-weight: normal;" : "") ."\">";
            $codeSnippet = $showCodeSnippet ? $this->_codeSnippetHtml( $file, $line ) : false;
            // 代码有可能无法获取，比如使用zend加密过的代码
            if( $codeSnippet ) {
                // 把代码片段隐藏起来，用户需要点击后展开，这样更友好
                // 那意味着我需要添加一些javascript
                // 代码片段用600像素的长度，正常情况接近约80个字符
                // 这样是希望程序员们写出更好的代码 :)
                $tr .= "<a href=\"javascript:void(0);\" style=\"color: #000; text-decoration: none;\" onclick=\"
                        var codeMain = this.parentNode.getElementsByTagName( 'div' )[0],
                            codeStatus = this.getElementsByTagName( 'span' )[0];
                        if( codeMain.style.display == 'block' ) {
                            codeMain.style.display = 'none';
                            codeStatus.innerHTML = '[+]'
                        } else {
                            codeMain.style.display = 'block';
                            codeStatus.innerHTML = '[-]';
                        }
                    \"><span>[+]</span> {$title}</a>
                    <div style=\"width: 600px; margin-left: 20px; overflow: hidden; display: none;\">
                        {$codeSnippet}
                    </div>";
            } else {
                $tr .= $title;
            }
            $tr .= "</{$elem}>";
        } else {
            if( $elem === 'th' ) {
                $title = "{$this->_type}: {$this->_messageFormat}";
            } else {
                $title = "{$func}";
            }
            $tr .= "<{$elem} style=\"border: 1px solid #000; padding: 5px;\">
                    $title
                </{$elem}>";
        }
        return ( $tr . '</tr>' );
    }
    
    /**
     * 设置文本数据
     * 
     * 消息主体放在第一行，其它行则是按调用方法栈（如果有提供的话）的顺序依次输出
     * 它并没有取出消息相关的代码片段，实际上也不能这么干，所以比HTML简单得多
     * @return string
     */
    protected function _setText() {
        $text = '';
        $br = "\r\n";
        list( $file, $line ) = $this->_replaceEval( $this->_file, $this->_line );
        $text .= "{$this->_type}: {$this->_messageFormat} in {$file}({$line}){$br}";
        if( $this->_showTrace && is_array( $this->_trace ) && ! empty( $this->_trace ) ) {
            $text .= "Trace: {$br}";
            foreach( $this->_trace as $info ) {
                if( ! isset( $info['function'] ) ) {
                    continue;
                }
                $func = $info['function'];
                if( isset( $info['class'] ) ) {
                    $func = $info['class'] . $info['type'] . $func;
                }
                if( isset( $info['file'] ) ) {
                    list( $file, $line ) = $this->_replaceEval( $info['file'], $info['line'] );
                    $text .= "{$file}({$line}) --> {$func}";
                } else {
                    $text .= "{$func}";
                }
                $text .= $br;
            }
        }
        return trim( $text, $br );
    }
    
    /**
     * 替换可能因为使用eval方法使文件路径产生的eval描述后缀，并且修正文件路径中的行
     * 
     * 比如这样的路径：
     * /MelonFramework/Melon.php(21) : eval()'d code
     * 
     * @param string $file 文件路径
     * @param int $line 行号
     * @return array( file, line )
     */
    protected function _replaceEval( $file, $line ) {
        if ( strpos( $file, 'eval()\'d code' ) !== false ) {
            $evalExp = '/\((\d+)\)\s:\seval\(\)\'d\scode/';
            $match = array();
            preg_match( $evalExp, $file, $match );
            $line = $match[1];
            $file = preg_replace( $evalExp, '', $file );
        }
        return array( $file, $line );
    }
    
    /**
     * 取出代码片段的HTML
     * 
     * 原理是使用原生的highlight_file解释为HTML，再用换行符分割为数组（一个元素一行代码），然后取数组范围
     * 不过要修正HTML元素的开始、结束标签
     * 这方法有点HACK，但是很简单，我不用为此专门去编写一个语法高亮的工具
     * 
     * @param string $file 文件路径
     * @param int $focus 其实就是line，不过在这里我称为焦点比如合适，因为上下文是围绕这行展开的
     * 并且会为这行增加一条高亮背景，就像断点，连我也不相信自己竟然可以造出这么酷的东西，CSS太灵活了
     * @param int $range [可选] 上下文范围，程序会从焦点上面取$range行，焦点下面也取$range行
     * @param array $style [可选] 样式
     * 1. lineHeight 行高
     * 2. fontSize 字体大小
     * @return string
     */
    protected function _codeSnippetHtml( $file, $focus, $range = 7, $style = array( 'lineHeight' => 20, 'fontSize' => 13 ) ) {
        $html = @highlight_file( $file, true );
        if( ! $html ) {
            return false;
        }
        $br = '<br />';
        // 分割html保存到数组
        $htmlLines = explode( $br, $html );
        // 这html其实挺大的，没用了先清掉
        unset($html);
        $linesCount = count( $htmlLines );
        // 行号的html
        $lineHtml = '';
        // 代码的html
        $codeHtml = '';

        // 获取相应范围的代码
        // 要注意边界，比如焦点在第一行，那再上面应该是没有代码的
        $start = ( ( $focus - $range ) < 1 ? 1 : ($focus - $range) );
        // 下面也是
        $end = ( ( $focus + $range ) > $linesCount ? $linesCount : ( $focus + $range ) );
        for( $line = ( $start - 1 ); $line < $end; $line++ ) {
            // 在行号前填充0，看起来更整齐一些
            $index_pad = str_pad( $line + 1, strlen( $linesCount ), 0, STR_PAD_LEFT );
            $lineHtml .= $index_pad . $br;
            $codeHtml .= $htmlLines[ $line ] . $br;
        }

        // 修正开始标签
        // 有可能取到的片段缺少开始的span标签，而它包含代码着色的CSS属性
        // 如果缺少，片段开始的代码则没有颜色了，所以需要把它找出来
        if( substr( $codeHtml, 0, 5 ) !== '<span' ) {
            $index = $start - 1;
            // 在范围外一直向上找到开始标签
            while( $index > 0 ) {
                $match = array();
                // 找到了后，只需要拿到颜色的属性，让开始范围的代码重新着色即可
                preg_match( '/<span style="color: #([\w]+)"(.(?!<\/span>))+$/', $htmlLines[ --$index ], $match );
                if( ! empty( $match ) ) {
                    $codeHtml = "<span style=\"color: #{$match[1]}\">" . $codeHtml;
                    break;
                }
            }
        }
        // 修正结束标签
        if( substr( $codeHtml, -7 ) !== '</span>' ) {
            $codeHtml .= '</span>';
        }
        
        // 现在可以生成一个包含行号和焦点高亮的代码块
        // 这CSS写得我够呛。。
        $hightLinePosistion = ( ( $focus - $start ) * $style['lineHeight'] );
        return <<<EOT
            <div style="position: relative; font-size: {$style['fontSize']}px;">
                <span style="display: block; position: absolute; z-index: 1; top: {$hightLinePosistion}px; height: {$style['lineHeight']}px; width: 100%; _width: 95%; background-color: yellow; opacity: 0.4; filter:alpha(opacity=40); "></span>
                <div style="float: left; margin-right: 10px; position: relative; z-index: 2; line-height: {$style['lineHeight']}px; color: #aaa;">{$lineHtml}</div>
                <div style="_width: 95%; line-height: {$style['lineHeight']}px; position: relative; z-index: 2; overflow: hidden; white-space:nowrap; text-overflow:ellipsis;">{$codeHtml}</div>
            </div>
EOT;
    }
}