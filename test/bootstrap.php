<?php
require_once dirname(dirname(__FILE__)) . "/vendor/autoload.php";
require_once dirname(dirname(__FILE__)) . '/test/config/define.php';

// init
\Swoft\Bean\BeanFactory::init();
\Swoft\Bean\BeanFactory::reload();
$initApplicationContext = new \Swoft\Core\InitApplicationContext();
$initApplicationContext->init();
\Swoft\App::$isInTest = true;
