<?php
define("BASE_PATH", getenv('BASE_PATH'));
define("CONTAINER_PATH", BASE_PATH.'/service-file');
define("APP_PATH", CONTAINER_PATH.'/app/application');

$libPath = BASE_PATH.'/include';
$commonLibPath = BASE_PATH.'/libraries/common';
$sfLibPath = BASE_PATH.'/libraries/service-file';
set_include_path($libPath.PATH_SEPARATOR.$commonLibPath.PATH_SEPARATOR.$sfLibPath);

define("TMP_PATH", CONTAINER_PATH.'/tmp');

define("APP_ENV", getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');
define("LOCAL_CHARSET", getenv('LOCAL_CHARSET') ? getenv('LOCAL_CHARSET') : 'UTF-8');

require_once $sfLibPath.'/Class/Server.php';
Class_Server::config(APP_ENV);

require_once $libPath."/Zend/Application.php";
$application = new Zend_Application(APP_ENV, APP_PATH.'/configs/application.ini');
$application->bootstrap()->run();