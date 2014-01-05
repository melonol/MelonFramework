什么是MelonFramework
-------------
MelonFramework是一个用于php5.3或以上开源的轻量级php框架，基于[Apache Licence 2.0](http://www.apache.org/licenses/LICENSE-2.0)开源协议发布。可零配置，支持restful程序的构建，并有可动态扩展的模块引擎、独创的包体系、触发器（类AOP）等功能。<br />
MelonFramework不是一个MVC框架（但你可以使用MelonFramework搭建出MVC），目前也不提供ORM操作，因为PDO已经足够好了，后期可能考虑增加简单的ORM。<br />
这样你不用拘泥于那些开发模式，框架提供了常见的基本操作，可以帮助你快速构建适合自己的开发环境。

使用简介
-------------
###初始化
只要引入Melon.php，执行初始化即可使用<br />
Melon主体类是一个纯静态类，提供了框架几乎所有操作，你只需要参阅Melon下的方法就能了解整个框架的所有功能
<pre>
<code>
	require './MelonFramework/Melon.php';
	Melon::init();
</code>
</pre>
主体类带有一个快捷方式M（其实是Melon的子类）<br />
你可以<code>Melon::env</code>这样调用一个方法，或者<code>M::env</code><br />
干脆你不想用它们，也可以自己换一个'马甲'，像M一样继承Melon：<br />
<pre>
<code>
	require './MelonFramework/Melon.php';
	class Name extends Melon {}
	Name::init();
</code>
</pre>
然后你就可以在任何地方使用Name了<br />
另外继承之后，可以往里添加一些自己的操作方法，非常方便

###配置
配置文件处于Melon/Data/Conf/Base.php下，它作为缺省配置<br />
如果需要自定义，可以在初始化的时候进行
<pre>
<code>
	require './MelonFramework/Melon.php';
	Melon::init(
		// root（应用目录）
		// Melon将以root为参照目录
		// 计算文件中的errorPage、logDir等文件的绝对路径，同时添加到inlucePath中
		 __DIR__,

		// 配置信息
		// 这里的参数值将覆盖Melon/Data/Conf/Base.php文件下的缺省值
		// 当然你可以增加一些自己的参数，使用Melon::env( 'config.keyname' ) 来获取这些值
		array(
			'logDir' => 'Melon/Data/Log',
			'errorPage' => 'Melon/Data/errorPage.html',
			'logLevel' => 3,
			'logDisplayLevel' => 3,
			'includePath' = array(
				// 添加更多的包含路径
			);
		)
	);
</code>
</pre>

###inlucePath（包含路径）
配置里有提到inlucePath，它是loader － 包括autoload、权限审查等函数的工作范围<br />
如果要载入的文件或目录不在includePath中，则它不在autoLoad的检查范围，也不具有权限和包的特征<br />

###autoLoad
autoLoad以命名空间作为目录依据，大部分框架都是这么做的<br />
当实例化一个不存在的类时，程序根据命名空间在includePath寻找<br />
类名必需和文件名相同

REST
-------------
如果只想简单的使用REST，框架带有一个小巧的REST类
<pre>
<code>
	// 获取一个REST实例
	$simpleRest = Melon::httpSimpleRest();

	// 添加一些规则，一旦被匹配到，则马上会执行回调函数
	// 路由规则请参考Melon\Http\Route类

	$simpleRest->get('/', function() {
		echo '欢迎来到Melon的世界！';
	});

	$simpleRest->post('/[type]/[id]', function( $type, $id ) {
		// 获取post参数中data的值
		$data = Melon::httpRequest()->input( 'data' );
	});

	$simpleRest->delete('/[type]/[id]', function( $type, $id ) {
		...
	});

	// 如果没有规则被匹配，输出404
	if( ! $simpleRest->matchTotal() ) {
		Melon::httpResponse()->send( '找不到页面了', 404 );
	}
</code>
</pre>

如果你需要更灵活的REST处理，比如HTTP认证、各种状态回应等，Melon::http* 系列方法都提供了相关操作（详情请参阅这些方法）

文件载入权限
-------------
在includePath中，当一个文件或目录名字被加上前缀 _ 的时候，它就被添加了相关读取限制，可以理解成设定为私有文件或目录<br />
在这之前我想介绍一下加载的方法，如Melon::load<br />
Melon的加载方法解决了php相对路径的问题（当然这对于php来说不是BUG），php是相对于域名根目录，而Melon的加载方法相对于文件，更符合正常的使用习惯
在任何地方使用Melon::load加载相对路径文件都会被正确的转换为绝对路径
<pre>
<code>
	// 当前路径 /www/Melon/index.php
	Melon::load( './app.php' ); // 被转化为/www/Melon/app.php，并载入
</code>
</pre>
原理是我之前通过研究debug_backtrace得到上级调用路径的一些案例，有兴趣的话可以查看这篇文章[PHP debug_backtrace的胡思乱想](http://my.oschina.net/u/867608/blog/129125)了解
通过debug_backtrace得到调用路径后，可以做一些关于权限的处理

没有权限的脚本要使用Melon::load等加载方法载入私有文件，会被抛出异常。要判断一个脚本文件是否有载入私有文件<br />
我把它们分别叫做'载入源路径'和'目标路径'，当载入源路径满足以下条件时，才有权限载入目标路径<br />
1. 目标路径不在检查范围内，即不在包含路径中<br />
2. 目标路径文件和父目录都不属于私有的<br />
3. 某个父目录属于私有，但是载入源也在这个私有目录或者其子目录下<br />
4. 载入源文件名与目标路径的当前父目录同级，载入源文件名（不含.php）加上私有前缀与当前父目录相等，比如 File.php和_File

包（package）
-------------
在了解包之前请先阅读文件载入权限<br />
当一个目录被设定为私有时，那么同时它也成为了一个'包'，私有目录必需有一个同名文件作为入口才可以读取里面的文件，这样加强了模块化的作用<br />
在包里任何脚本都可以使用<code>Melon::packageDir();</code>得到当前包的目录<br />
同时也可以使用<code>Melon::packageAcquire</code><code>Melon::packageLoad</code>等方法

模板
-------------
模板是通过定义一系统标签，使用正则表达式替换为标准的PHP语法的格式<br />
<pre>
<code>
	Melon::template()
	// 设置模板目录
	->setTemplateDir( './' )
	// 设置编译缓存目录
	->setCompileDir( './' )
	// 注入变量
	->assign( $key, $value )
	// 显示模板
	->display( 'index.html' );
</code>
</pre>
可使用的标签如下（以标签符是{}为例子）<br />
###常规标签
<pre>
<code>
	{$var} 注：输出一个变量，可使用assign或assignItem方法注入这些变量，如果变量是数组，键名无需添加引号：{$arr[key]}
	{if 条件} 内容 {/if}
	{if 条件} 内容 {else} 内容 {/if}
	{if 条件} 内容 {elseif 条件} 内容 {/if}
	{foreach $arr $value} 内容 {/foreach}
	{foreach $arr $key $value} 内容 {/foreach}
	{print 变量或函数}  注：可以使用print标签对内容进行处理，比如 {print date( 'Y-m-d', 
	{php php代码/}
	{php} php代码 {/php}
	{include 子模板路径}  注：可在模板中引入子模板
</code>
</pre>

###模板继承
<pre>
<code>
	{extend 继承模板路径/}
	{block 块名称} 块内容 {/block}
	如果你熟悉smarty中的继承，应该不难理解，使用方法基本类似
	继承标签由extend和block标签共同完成
	继承模板中的block会覆盖父模板中的同名block内容
	如果没有覆盖（同名块）父模板某个block，则使用这个block中默认的内容
</code>
</pre>

###标签扩展
<pre>
<code>
	{tag:标签名 属性=值} 内容 {/tag}  注：可使用assignTag或assignTagItem方法添加自定
	你可以在模板中使用这个自定义标签
	// 声明一个获取某个列表数据的函数
	function getList( $id, $limit ) {
			// 返回一个列表数据
	}
	// 定义一个list标签
	$template->assignTag( 'list', array(
			'callable' => 'getList',
			'args' => array( 'id' => 1, 'limit' => 10 )
	) );

	如果getList返回一个数组，在模板中就可以这样使用，程序会自动遍历这个数组：
	{tag:list id=1}
			{$data} //$data是getList返回的数组中的每个元素的值
	{/tag:list}
	参数可使用变量，同时也可以自定义遍历的元素值的名称：
	{tag:list id=$id result=row}
			{$row}
	{/tag:list}

	如果getList返回一个字符串，在模板中可以这样使用，程序会输出这个字符串：
	{tag:list id=1 /}
</code>
</pre>
另外，标签也可以互相嵌套，没有限制

触发器
-------------
事件触发器，可以实现一些类似AOP的操作
<pre>
<code>
	class Test {
		public function info( $name ) {
			$text = 'Hello ' . $name;
			echo $text;
			return $text;
		}
	}

	// 绑定触发事件
	$testTrigger = Melon::trigger( new Test(), array(
		'info' => function( $arg1 ) {
			echo '执行前，参数是：' . $arg1;
		}
	), array(
		'info' => function( $result ) {
			echo '执行后，返回结果是：' . $result;
		}
	) );

	$testTrigger->info( 'Melon' );
	// 输出：
	// 执行前，参数是：Melon
	// Hello Melon
	// 执行后，返回结果是：Hello Melon
</code>
</pre>

错误与调试
-------------
常规错误可直接使用原生的<code>rigger_error</code><br />
异常使用<code>Melon::throwException</code><br />
调试使用<code>Melon::debug</code>或<code>Melon::debugWithTrace</code>方法<br />
程序会在页面和日志文件中写入相关信息，你可以通过配置来开启或关闭这些功能<br />
程序还提供了显示代码片段功能，在页面显示错误时点击相应行可展开相关代码片段
<p>
<img src="http://static.oschina.net/uploads/space/2014/0105/220508_7WFM_867608.png" />
</p>
另外针对ajax和cgi调用部分做了处理，通过这些接口访问php时会显示文本，而不是html

文档
-------------
文档正在筹备中，参阅Melon下的方法就能大概了解整个框架的所有功能

意见和建议
-------------
>如果在使用过程中发现任何问题，或有任何建议，都欢迎你发送邮件到这个地址： denglh1990@qq.com