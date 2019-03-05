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

try{
    $app = new \Swoolf\App(APP_PATH . '/lib/application.ini');
    Swoolf\Protocol\ProtoBufMessage::setProto([
        1001 => 'RequestLogin',
        1002 => 'ResponseLogin',
        1003 => 'RequestGetFriendList',
        1004 => 'ResponseGetFriendList',
        1005 => 'RequestLogout',
        1006 => 'ResponseLogout',
        1007 => 'RequestSendMessage',
        1008 => 'ResponseSendMessage',
        1010 => 'ResponseReceiveMessage',
        1011 => 'RequestGetHistoryMessage',
        1012 => 'ResponseGetHistoryMessage',
    ]);
    $app->run();
} catch (\Swoolf\SwoolfException $e) {
    \Swoolf\Log::err($e->getMessage());
} catch (\Exception $e) {
    \Swoolf\Log::err($e->getMessage());
}
