<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/15
 * Time: 11:30
 */
define('APP_PATH', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);
include_once APP_PATH . DS . 'lib' . DS . 'Swoolf' . DS . 'Loader.php';

\Swoolf\Log::log('console log msg.');
\Swoolf\Log::err('error msg.');
\Swoolf\Log::warm('warning msg.');
\Swoolf\Log::info('it is begin.');
\Swoolf\Log::ok('operation success.');