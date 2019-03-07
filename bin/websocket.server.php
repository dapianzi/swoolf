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
    $app = new \Swoolf\App(APP_PATH . '/conf/application.ini');
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
        1009 => [
            'proto' => 'ResponseUserOnline'
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
    $app->loader()::regNamespace('App', APP_PATH.'/application');
    $app->on('message', function($server, $frame) use($app) {
//        \Swoolf\Log::log('');
        $dispatcher = $app->dispatcher;
        if ($dispatcher->dispatch($frame->fd, $frame->data)) {
            $controller = $dispatcher->controller.'Controller';
            $action = $dispatcher->action.'Action';
            $obj = new $controller($dispatcher->fd, $dispatcher->request);
            try {
                $obj->$action();
                unset($obj);
            } catch (\Exception $e) {
                $app->log()::err($e->getMessage());
                $app->log()::log($e->getTraceAsString());
            }
        } else {
            $app->log()::err('Unpack error:'.$frame->data);
            $app->log()::warm(sprintf('Unpack message from fd[%d], ip[%s]', $frame->fd, $server->getClientInfo($frame->fd)['remote_ip']));
            return false;
        }
    });
    $app->on('task', function($serv, $task_id, $src_worker_id, $data) use($app) {
//        $data = msgpack_unpack($data);
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
                    $info = $serv->connection_info($fd);
                    if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
                        $serv->push($fd, $data['response'], WEBSOCKET_OPCODE_BINARY);
                    }
                }
                $app->log()::info(sprintf('Broadcast finish at %f', microtime(TRUE)));
        }
        $serv->finish('ok');
    });
    $app->on('workerStop', function($server, $worker_id) use ($app) {
        $app->redis->del('user');
    });
    $app->run();
} catch (\Exception $e) {
    \Swoolf\Log::err($e->getMessage());
}
