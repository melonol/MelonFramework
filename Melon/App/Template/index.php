<?php

require '../MelonFramework/Melon.php';

Melon::init( array(
	'type' => 'app',
	'root' => __DIR__,
	'appName' => '__APPNAME__',
	'moduleName' => '__MODULENAME__',
	'install' => true,
	'baseConfig' => array(
		'logDisplayLevel' => 3,
		'logDir' => 'Log'
	),
	'dbConfig' => array(
		'tablePrefix' => '',
		'driver' => array(
			'dsn' => 'mysql:host=localhost;dbname=test;',
			'username' => 'root',
			'password' => '123456',
			'options' => array(),
		)
	)
) );

Melon::runApp();
