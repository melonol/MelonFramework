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
 * URL路由解释
 * 
 * <pre>
 * 使用路由需要在服务器中添加一条重写规则，把所有路径重写到当前域名根目录下的某个文件中，一般是index.php
 * 你可以参考本框架目录下的.htaccess文件
 * 
 * 路由的作用是根据配置规则，以/号作为路径目录分割符，匹配URL中的PATHINFO，并替换为对应的URL
 * 如果没有PATHINFO，则使用REQUEST_URI来解释
 * 
 * 规则有两种匹配模式，每条规则只能使用其中一种，不能混用
 * 大部分情况下，建议尽量使用第1种
 * 
 * 1. 按命名组的方式匹配，例
 * 配置： /[type]/[id] => /category/[type]/[id]
 * 这种方式无需写正则表达式，直接定义参数名，好处是语义化强，可读性较好
 * 也可以在:号后添加正则表达式的规则：
 * /[type:\w+]/[id:\d+] => /category/[type]/[id]
 * 某些情况，如果你需要在表达式中使用/号，请在前面加上\号进行转义，否则会被认为是路径目录分割符
 * 如果要使用*号（通配符），请加上?号进入懒惰匹配模式，否则可能会把余下分组的信息覆盖，因为*包含/分割符
 * 
 * 2.普通正则匹配，例
 * 配置： /(\w+)/(\d+) => /category/$1/$2
 * 这种方式虽然灵活，但可读性较差， 一般情况不建议使用
 * </pre>
 * 
 * @package Melon
 * @since 0.1.0
 * @author Melon
 */
class Route {
    
    /**
     * 路由类型，自动识别
     */
    const TYPE_AUTO = 0;
    
    /**
     * 路由类型，不完全的（带.php）
     */
    const TYPE_INCOMPLETE_PATHINFO = 1;
    
    /**
     * 路由类型，完全的（不带.php）
     */
    const TYPE_COMPLETE_PATHINFO = 2;
    
    /**
     * 通过请求参数指定路由
     */
    const TYPE_REQUEST_KEY = 3;
    
    /**
     * 路由类型
     * 
     * @var enum
     */
    protected $_type;
    
    /**
     * 请求参数的key
    
     *  * @var enum
     */
    protected $_requestKey;

    /**
     * 配置文件
     * 
     * @var array
     */
    protected $_config = array();
    
    /**
     * 当前请求方法
     * 
     * @var string
     */
    protected $_method;
    
    /**
     * URL中的PATHINFO
     * 
     * @var string
     */
    protected $_pathInfo;

    /**
     * 构造器，实例化时请提供相关配置参数
     * 
     * @param array $config 全局路由配置，详情请看self::setConfig方法
     * @param enum $type 路由类型
     * Route::TYPE_AUTO                [默认] 自动识别
     * Route::TYPE_INCOMPLETE          不完全的（带.php）
     * Route::TYPE_COMPLETE            完全的（带.php）
     * Route::TYPE_REQUEST_KEY         通过请求参数指定路由
     * @param string $request 请求参数的名字，当路由类型为Route::TYPE_REQUEST_KEY时，有效
     */
    public function __construct( $config = array(), $type = Route::TYPE_AUTO, $requestKey = '' ) {
        $this->setConfig( $config );
        $this->_type = $type;
        $this->_requestKey = $requestKey;
        $this->_setPathInfo();
        $this->_method = strtolower( \Melon::httpRequest()->method() );
    }
    
    /**
     * 设置路由规则
     * 
     * <pre>
     * 例：
     * $route->setConfig( array(
     *    'global' => array(
     *        '/[type]/[id]' => '/category/[type]/[id]',
     *        '/user/(\w+)' => '/user/id/$1'
     *    ),
     *    'get' => array(
     *        '/comment/hot/[uid]' => '/bolg/comment/type/hot/user/[uid]'
     *        ...
     *    ),
     *    'post' => array( ... ),
     *    ...
     * ) );
     * </pre>
     * 
     * @param array $config 配置规则
     * global是全局路由规则，当某个方法的路由不存在或没有匹配时使用
     * 其它你可以为某个请求方法指定规则，这种规则优先级比global要高
     * 
     * @return \Melon\Http\Route
     */
    public function setConfig( $config ) {
        $this->_config = is_array( $config ) ? $config : array();
        return $this;
    }
    
    /**
     * 获取路由规则
     * 
     * 把当前请求方法和路由规则（如果有的话）和global规则合并返回
     * 并且，方法的规则优先级比global高
     * 
     * @return array
     */
    protected function _getRules() {
        $rules = ( isset( $this->_config['global'] ) && is_array( $this->_config['global'] ) ?
            $this->_config['global'] : array() );
        if( isset( $this->_config[ $this->_method ] ) && is_array( $this->_config[ $this->_method] ) ) {
            foreach( $this->_config[ $this->_method ] as $exp => $replace ) {
                if( isset( $rules[ $exp ] ) ) {
                    unset( $rules[ $exp ] );
                }
            }
            $rules = array_merge( $this->_config[ $this->_method ], $rules );
        }
        return $rules;
    }
    
    /**
     * 获取PATHINFO
     * 
     * @return string
     */
    public function pathInfo() {
        return $this->_pathInfo;
    }
    
    /**
     * 解释PATHINFO
     * 
     * @return void
     */
    protected function _setPathInfo() {
        $pathInfo = ( isset($_SERVER['PATH_INFO'] ) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO') );
        //如果服务器不支持PATH_INFO，则使用REQUEST_URI解析
        if( empty( $pathInfo ) && $this->_type !== self::TYPE_REQUEST_KEY ) {
            if( isset( $_SERVER['REQUEST_URI'] ) && stripos( $_SERVER['REQUEST_URI'], '.php?' ) === false &&
                substr( $_SERVER['REQUEST_URI'], -4 ) !== '.php' ) {
                
                // 先除掉当前目录，以当前目录为起始向后匹配
                $quoteDir = '/^' . str_replace( '/', '\/', preg_quote( dirname( $_SERVER['SCRIPT_NAME'] ) ) ) . '/i';
                $requestUri = preg_replace( $quoteDir, '', $_SERVER['REQUEST_URI'] );
                $match = array();
                if( $requestUri && preg_match( "#^[^?]+#", $requestUri, $match ) ) {
                    //替换多余的 / 号
                    $pathInfo = preg_replace( '#/+#', '/', $match[0] );
                }
            }
        }
        if( $this->_type === self::TYPE_REQUEST_KEY || ( empty( $pathInfo ) && $this->_type === self::TYPE_AUTO ) ) {
            $pathInfo = \Melon::httpRequest()->input( $this->_requestKey, 'g' );
        }
        if( empty( $pathInfo ) ) {
            $pathInfo = '/';
        }
        $this->_pathInfo = trim( $pathInfo, '/' );
    }
    
    /**
     * 解释路由
     * 
     * @param &array $parseInfo 解释成功后，会把相关匹配信息填充到该变量中，如果解释失败，则不会填充
     * 里面有两个值：
     * 1. rule        string    匹配成功的路由规则
     * 2. args        array     匹配到的URL分组数据
     * @return string 经过匹配并替换后的URL
     */
    public function parse( & $parseInfo = array() ) {
        $pathInfo = $this->pathInfo();
        foreach( $this->_getRules() as $exp => $replace ) {
            // 去除第一个 / 号
            $_exp = ( ( $exp[0] === '/' ) ? substr( $exp, 1 ) : $exp );
            // 如果都为空，则表示当前处于域名根目录
            // 这种情况被认为是匹配成功
            if( ! $pathInfo && ! $_exp ) {
                $parseInfo = array(
                    'args' => array(),
                    'rule' => $exp
                );
                break;
            }
            $expInfo = array();
            $group = array();
            // 以/号分割规则
            foreach( preg_split( '/(?<!\\\\)\//', $_exp ) as $elem ) {
                $matchGroup = array();
                // 解释分组，得到分组信息，并转换为正则表达式
                if( $_exp && $elem[0] === '[' && preg_match( '/^\[(\w+)(?::(.*))?\]$/', $elem, $matchGroup ) ) {
                    $group[ $matchGroup[1] ] = "[{$matchGroup[1]}]";
                    $elem = ( isset( $matchGroup[2] ) ?
                        "(?<{$matchGroup[1]}>{$matchGroup[2]})" : "(?<{$matchGroup[1]}>[^\/]+)" );
                }
                // 重新整合规则
                $expInfo[] = $elem;
            }
            $_exp = implode( '/', $expInfo );
            if( preg_match( "#^{$_exp}$#i", $pathInfo, $match ) ) {
                if( $group ) {
                    $replaceList = array();
                    foreach( $group as $name => $value ) {
                        $replaceList[] = $match[ $name ];
                    }
                    $parseInfo = array(
                        'args' => $replaceList,
                        'rule' => $exp
                    );
                    return str_replace( $group, $replaceList, $replace );
                } else {
                    unset( $match[0] );
                    $parseInfo = array(
                        'args' => $match,
                        'rule' => $exp
                    );
                    return preg_replace( "#^{$_exp}$#i", $replace, $pathInfo );
                }
            }
        }
        return $pathInfo;
    }
}