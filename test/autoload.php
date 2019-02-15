]<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 16:31
 */

define('APP_PATH', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);
include_once APP_PATH . DS . 'lib' . DS . 'Swoolf' . DS . 'Loader.php';

(new \Vendor\A\B())->talk();
// register other lib dir root.
\Swoolf\Loader::regNamespace('Test', APP_PATH . DS . 'test');
(new \Test\Autoload\B())->talk();
(new \Test\Other())->talk();