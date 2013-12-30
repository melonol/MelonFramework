<?php

namespace Melon\Http;

defined('IN_MELON') or die('Permission denied');

class Response {

	/**
	 * HTTP版本
	 * @var string
	 */
	private $_httpVersion;

	/**
	 * 状态码
	 * @var type 
	 */
	private $_status = 200;

	/**
	 * 媒体内容
	 * @var string
	 */
	private $_body;

	/**
	 * 媒体类型
	 * @var string
	 * @link http://zh.wikipedia.org/wiki/%E4%BA%92%E8%81%94%E7%BD%91%E5%AA%92%E4%BD%93%E7%B1%BB%E5%9E%8B
	 */
	private $_contentType;

	/**
	 * 媒体编码
	 * @var string
	 */
	private $_charset;

	/**
	 * HTTP头设置
	 * @var array
	 * @link http://kb.cnblogs.com/page/92320/
	 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
	 */
	private $_header = array();

	/**
	 * 状态码消息
	 * @var array
	 * @link http://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
	 */
	private $_statusMessage = array(
		//请求消息
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
		//重定向
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => '(Unused)',
		307 => 'Temporary Redirect',
		//请求错误
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
	 * @param string $httpVersion [optional] 要使用哪个HTTP版本，为空则使用默认值
	 */
	public function __construct($httpVersion = '1.1', $charset = 'utf-8', $contentType = 'text/html') {
		$this->_httpVersion = $httpVersion;
		$this->_charset = $charset;
		$this->_contentType = $contentType;
	}

	/**
	 * 发送回应结果
	 * 
	 * @param string $body [optional] 媒体内容
	 * @param int $status [optional] 状态码
	 * @param string $contentType [optional] 媒体格式
	 */
	public function send($body = '', $status = null, $contentType = null) {
		if (!empty($body)) {
			$this->setBody($body);
		}
		if (!is_null($status)) {
			$this->setStatus($status);
		}
		if (!empty($contentType)) {
			$this->setContentType($contentType);
		}
		if( ! headers_sent() ) {
			$message = $this->getStatusMessage();
			if(CLIENT_TYPE === 'CGI') {
				header("Status:{$message}");
			} else {
				header("HTTP/{$this->_httpVersion} {$this->_status} {$message}");
			}
			header("Content-Type:{$this->_contentType};charset={$this->_charset}");
			foreach ($this->_header as $name => $value) {
				header("{$name}:{$value}");
			}
		}
		//内容
		if (!empty($this->_body)) {
			echo $this->_body;
		}
		return $this;
	}

	/**
	 * 设置状态码
	 * 
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->_status = intval($status);
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
		return isset($this->_statusMessage[$this->_status]) ?
				$this->_statusMessage[$this->_status] : 'Unknown';
	}

	/**
	 * 设置媒体内容
	 * 
	 * @param string $body
	 */
	public function setBody($body) {
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
	public function setContentType($contentType) {
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
	public function setCharset($charset) {
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
	 * @param string $name 配置
	 * @param string $value 值
	 */
	public function setHeader($name, $value) {
		$this->_header[$name] = $value;
		return $this;
	}

	/**
	 * 设置HTTP头
	 * 
	 * @param string $name 配置
	 * @param string $value 值
	 */
	public function setHeaderItem($headers) {
		foreach($headers as $name => $value) {
			$this->setHeader($name, $value);
		}
		return $this;
	}

}