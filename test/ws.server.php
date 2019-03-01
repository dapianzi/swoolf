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
//        \Swoolf\Log::log('Receive msg from fd['.$frame->fd.']:'.$frame->data);
        if ($this->dispatcher->dispatch($frame->fd, $frame->data)) {
            $controller = $this->dispatcher->controller.'Controller';
            $action = $this->dispatcher->action.'Action';
            $obj = new $controller($this);
            try {
                $obj->$action();
                unset($obj);
            } catch (\Exception $e) {
                $this->log::err($e->getMessage());
                $this->log::log($e->getTraceAsString());
            }
        } else {
            $this->log::err('Unpack error:'.$frame->data);
            $this->log::warm(sprintf('Unpack message from fd[%d], ip[%s]', $frame->fd, $this->server->getClientInfo($frame->fd)['remote_ip']));
            return false;
        }
    }
}

try{
    $app = myApp::getInstance(APP_PATH.'/conf/application.ini');
    \Swoolf\Dispatcher::setRules([
        1001 => [
            'proto' => 'RequestLogin',
            'controller' => '\\App\\Controller\\Account',
            'action' => 'Login'
        ],
        1002 => [
            'proto' => 'ResponseLogin',
        ],
        1003 => [
            'proto' => 'RequestGetFriendList',
            'controller' => '\\App\\Controller\\Account',
            'action' => 'GetFriendList'
        ],
        1004 => [
            'proto' => 'ResponseGetFriendList'
        ],
        1005 => [
            'proto' => 'RequestLogout',
            'controller' => '\\App\\Controller\\Account',
            'action' => 'Logout'
        ],
        1006 => [
            'proto' => 'ResponseLogout',
        ],
        1007 => [
            'proto' => 'RequestSendMessage',
            'controller' => '\\App\\Controller\\Message',
            'action' => 'SendMessage'
        ],
        1008 => [
            'proto' => 'ResponseSendMessage'
        ],
        1010 => [
            'proto' => 'ResponseReceiveMessage'
//            'proto' => 'NotifyReceiveMessage'
        ],
        1011 => [
            'proto' => 'RequestGetHistoryMessage',
            'controller' => '\\App\\Controller\\Message',
            'action' => 'MessageHistory'
        ],
        1012 => [
            'proto' => 'ResponseGetHistoryMessage'
        ]
    ]);
    $app->loader::regNamespace('App', APP_PATH.'/application');
    $app->event::add('login', function($fd) use ($app) {
        $app->table->set($fd, [
            'id' => $fd,
            'name' => '用户'.$fd,
            'icon' => 'http://192.168.1.27:8905/default/avatar_'.($fd%9+1).'.jpg'
        ]);
    });
    $app->event::add('logout', function($fd) use ($app) {
        $app->table->del($fd);
    });
    $app->run();
} catch (\Exception $e) {
    \Swoolf\Log::err($e->getMessage());
}
