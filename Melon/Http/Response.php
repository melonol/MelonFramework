<?php
/**
 * Melon － 可用于php5.3或以上的开源框架
 * 
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://framework.melonol.com
 * @author Melon <admin@melonol.com>
 * @version 0.2.3
 */

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

/**
 * Response用于简单的回应HTTP请求
 * 
 * 没有做过多处理，只有非常简单的回应头、状态码、媒体类型和内容的设置
 * 大部分情况都够用，同时已针对cgi请求做了处理
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
class Response {

    /**
     * HTTP版本
     * 
     * @var string
     */
    protected $_httpVersion;

    /**
     * 状态码
     * 
     * @var type 
     */
    protected $_status = 200;

    /**
     * 媒体内容
     * 
     * @var string
     */
    protected $_body;

    /**
     * 媒体类型
     * 
     * @var string
     * @link http://zh.wikipedia.org/wiki/%E4%BA%92%E8%81%94%E7%BD%91%E5%AA%92%E4%BD%93%E7%B1%BB%E5%9E%8B
     */
    protected $_contentType;

    /**
     * 媒体编码
     * 
     * @var string
     */
    protected $_charset;

    /**
     * HTTP头设置
     * 
     * @var array
     * @link http://kb.cnblogs.com/page/92320/
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
     */
    protected $_header = array();

    /**
     * 状态码消息
     * 
     * @var array
     * @link http://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
     */
    protected $_statusMessage = array(
        // 请求消息
        100 => 'Continue',
        101 => 'Switching Protocols',
        //成功
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // 重定向
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        // 请求错误
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        //服务器错误
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    );

    /**
     * 构造器
     * 
     * @param string $httpVersion [可选] 要使用哪个HTTP版本，为空则使用默认值
     * @param string $charset [可选] 回应内容的编码
     * @param string $contentType [可选] 媒体类型
     */
    public function __construct( $httpVersion = '1.1', $charset = 'utf-8', $contentType = 'text/html' ) {
        $this->_httpVersion = $httpVersion;
        $this->_charset = $charset;
        $this->_contentType = $contentType;
    }

    /**
     * 发送回应结果
     * 
     * 如果已经在调用本方法前输出过内容，则程序不会再调用header
     * 即使用过setHeader或者setHeaderItem设置的头信息将会失效
     * 
     * @param string $body [可选] 媒体内容，如果为空则使用预设值
     * @param int $status [可选] 状态码，如果为空则使用预设值
     * @param string $contentType [可选] 媒体格式，如果为空则使用预设值
     */
    public function send( $body = '', $status = null, $contentType = null ) {
        if( ! empty( $body ) ) {
            $this->setBody( $body );
        }
        if( ! is_null( $status ) ) {
            $this->setStatus( $status );
        }
        if( ! empty( $contentType ) ) {
            $this->setContentType( $contentType );
        }
        
        // 如果已经发送了内容，则再使用header是无效的，也会报错
        if( ! headers_sent() ) {
            $message = $this->getStatusMessage();
            if( \Melon::env( 'clientType' ) === 'cgi' ) {
                header( "Status:{$message}" );
            } else {
                header( "HTTP/{$this->_httpVersion} {$this->_status} {$message}" );
            }
            header( "Content-Type:{$this->_contentType};charset={$this->_charset}" );
            foreach ( $this->_header as $name => $value ) {
                header( "{$name}:{$value}" );
            }
        }
        // 输出内容
        if ( ! empty( $this->_body ) ) {
            echo $this->_body;
        }
        return $this;
    }

    /**
     * 设置状态码
     * 
     * @param int $status
     */
    public function setStatus( $status ) {
        $this->_status = intval( $status );
        return $this;
    }

    /**
     * 获取状态码
     * 
     * @return int
     */
    public function getStatus() {
        return $this->_status;
    }

    /**
     * 根据status获取相应的状态描述
     * 
     * @return string
     */
    public function getStatusMessage() {
        return ( isset( $this->_statusMessage[ $this->_status ] ) ?
                $this->_statusMessage[ $this->_status ] : 'Unknown' );
    }

    /**
     * 设置媒体内容
     * 
     * @param string $body
     */
    public function setBody( $body ) {
        $this->_body = strval( $body );
        return $this;
    }

    /**
     * 获取媒体内容
     * 
     * @return string
     */
    public function getBody() {
        return $this->_body;
    }

    /**
     * 设置媒体格式
     * 
     * @param string $contentType
     */
    public function setContentType( $contentType ) {
        $this->_contentType = $contentType;
        return $this;
    }

    /**
     * 获取媒体格式
     * 
     * @return string $content_type
     */
    public function getContentType() {
        return $this->_contentType;
    }

    /**
     * 设置媒体编码
     * 
     * @param string $charset
     */
    public function setCharset( $charset ) {
        $this->_charset = $charset;
        return $this;
    }

    /**
     * 获取媒体编码
     * 
     * @return string
     */
    public function getCharset() {
        return $this->_charset;
    }

    /**
     * 设置HTTP头
     * 
     * @param string $name 名称
     * @param string $value 值
     */
    public function setHeader( $name, $value ) {
        $this->_header[ $name ] = $value;
        return $this;
    }

    /**
     * 设置一组HTTP头
     * 
     * @param array $headers array( string => 名称, string => 值 )
     */
    public function setHeaderItem( $headers ) {
        foreach( $headers as $name => $value ) {
            $this->setHeader( $name, $value );
        }
        return $this;
    }

}