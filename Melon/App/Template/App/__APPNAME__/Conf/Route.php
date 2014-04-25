<?php

return array(
    'type' => 'requestKey',                 // 路由类型 requesKey使用请求参数，以当前配置key为根据，获取对应请求参数的值；incompletePathinfo不完整的（带.php）；completePathinfo完整的（不带.php）。要注意的是，如果Melon::runApp方法中指定了控制器，当前路由配置将不会被使用
    
    // 如果不使用路由，将使用以下参数指定控制器和方法
    'requestKey' => 'p',                    // 哪个请求参数表示路由
    
    'defaultModule' => '',                  // 默认模块
    'defaultController' => 'Index',         // 默认控制器
    'defaultAction' => 'Index',             // 默认方法
    
    '404' => 'Data/404.html',               // 404
    
    // 路由规则
    'global' => array(
        
    ),
    'get' => array(
        
    ),
    'post' => array(
        
    ),
    'put' => array(
        
    ),
    'delete' => array(
        
    )
    
);