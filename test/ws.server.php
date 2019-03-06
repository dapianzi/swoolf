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
    $app = new \Swoolf\App(APP_PATH.'/conf/application.ini');
    $app->on('task', function($serv, $task_id, $src_task_id, $data) {
        switch ($data['task_id']) {
            case 1001:
                $id = (new \App\Task\MessageTask())->saveMessageTask($data['data']);
                break;
            case 1002:
                (new \App\Task\MessageTask())->DBtestTask();
                break;
            default:
                // broadcast message
                foreach ($serv->connections as $fd) {
                    if (isset($data['fd']) && $fd == $data['fd']) {
                        continue;
                    }
                    $info = $this->server->connection_info($fd);
                    if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
                        $this->server->push($fd, $data['response'], WEBSOCKET_OPCODE_BINARY);
                    }
                }
                $this->log::info(sprintf('Broadcast finish at %f', microtime(TRUE)));
        }
        $serv->finish($data);
    });
    \Swoolf\Dispatcher::setRules([
        1001 => [
            'proto' => 'RequestRegister',
            'controller' => '\\App\\Controller\\Account',
            'action' => 'Register'
        ],
        1002 => [
            'proto' => 'ResponseRegister',
        ],
        1003 => [
            'proto' => 'RequestLogin',
            'controller' => '\\App\\Controller\\Account',
            'action' => 'Login'
        ],
        1004 => [
            'proto' => 'ResponseLogin'
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
//    $app->event::add('login', function($fd) use ($app) {
//        $app->table->set($fd, [
//            'id' => $fd,
//            'name' => '用户'.$fd,
//            'icon' => 'http://192.168.1.27:8905/default/avatar_'.($fd%9+1).'.jpg'
//        ]);
//    });
//    $app->event::add('logout', function($fd) use ($app) {
//        $app->table->del($fd);
//    });
    $app->run();
} catch (\Exception $e) {
    \Swoolf\Log::err($e->getMessage());
}
