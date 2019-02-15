#!/usr/bin/php
<?php
define('APP_PATH', dirname(dirname(__FILE__)));
define(DS, DIRECTORY_SEPARATOR);
include_once APP_PATH . DS . 'lib' . DS . 'Swoolf' . DS . 'Loader.php';
$serv = new \Swoolf\Core\Server\Http();
$serv->start();