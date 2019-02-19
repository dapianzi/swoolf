<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/19
 * Time: 20:37
 */

define('APP_ENV', 'develop');
define('APP_PATH', dirname(dirname(__FILE__)));
require APP_PATH . '/lib/Swoolf/Loader.php';


class myApp extends \Swoolf\App {
    public function onWSMessage($server, $frame)
    {
        \Swoolf\Log::info('Receive message from fd['.$frame->fd.']');
        $server->push('echo : '.$frame->data);
    }
}

myApp::getInstance(APP_PATH.'/conf/application.ini')->run();