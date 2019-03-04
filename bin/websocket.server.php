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


$app = new \Swoolf\App(APP_PATH . '/lib/application.ini');

$app->on('open', function($server, $request) use ($app) {

});
$app->on('message', function($server, $request) use ($app) {

});
    public function onWSMessage($server, $frame)
    {
//        \Swoolf\Log::log('Receive msg from fd['.$frame->fd.']:'.$frame->data);
        dispatch($server, $frame->fd, $frame->data);
//        \Swoolf\Log::warm($response);
//        $server->push($fd, $response);
//        foreach ($server->connections as $fd) {
//            if ($fd == $frame->fd) {
//                continue;
//            }
//            $info = $server->connection_info($fd);
//            if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
//                $server->push($fd, $response);
//            }
//        }
function dispatch($server, $fd, $buf) {
    $msg = self::unpack($buf);
    if (!$msg) {
        $this->log::err('Unpack error:'.$buf);
        $this->log::warm(sprintf('Unpack message from fd[%d], ip[%s]', $fd, $server->getClientInfo($fd)['remote_ip']));
        return false;
    }
    switch ($msg->msg_id) {
        case 1001:{
            (new TestController($fd, $msg))->RequestLogin();
            break;
        }
        case 1003:{
            (new TestController($fd, $msg))->RequestGetFriendList();
            break;
        }
        case 1005:{
            (new TestController($fd, $msg))->RequestLogout();
            break;
        }
        case 1007:{
            (new TestController($fd, $msg))->RequestSendMessage();
            break;
        }
        case 1011:{
            (new TestController($fd, $msg))->RequestGetHistoryMessage();
            break;
        }
        default: {
            $this->log::warm('Unresolved message id '.$msg->msg_id);
        }
    }
}

class TestController {
    public $fd;
    public $app = null;
    public $req = null;
    public $data = null;
    public $user = null;
    public function __construct($fd, $request)
    {
        $this->fd = $fd;
        $this->app = \Swoolf\App::getInstance();
        $this->data = $request->msg_data;
        $this->req = $request;
        $this->user = $this->app->table->get($this->fd);
    }

    public function response($fd, $msg_id, $data=null) {
        return $this->app->server->push($fd, myApp::pack($msg_id, $data), WEBSOCKET_OPCODE_BINARY);
    }

    public function RequestLogin() {
        if ($this->data->getUsername() == 'dapianzi' &&
            '40bd001563085fc35165329ea1ff5c5ecbdbbeef' == $this->app->utils::encryptStr($this->data->getPassword())) {
            $this->response($this->fd, 1002, ['id'=>$this->fd,'token' => $this->app->utils::randomStr(32)]);
            // init login
            $this->app->table->set($this->fd, [
                'id' => $this->fd,
                'name' => '用户'.$this->fd,
                'icon' => 'http://192.168.1.27:8905/default/avatar_'.rand(1,9).'.jpg'
            ]);
        } else {
            $this->response($this->fd, 1002, ['op' => 1]);
        }
    }
    public function RequestGetFriendList() {
        $list = [];
        foreach ($this->table as $r) {
            $list[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'icon' => $r['icon'],
            ];
        }
        $this->response($this->fd, 1004, ['list'=>$list]);
    }
    public function RequestLogout() {
        $this->response($this->fd, 1006);
        $this->app->server->close($this->fd);// close socket
    }
    public function RequestGetHistoryMessage() {

    }

    public function RequestSendMessage() {
        $this->response($this->fd, 1008, [
            'msgID' => $this->data->getMsg()->getMsgID()
        ]);
        // save message to db;
        // broadcast message
        // todo should done in a task
        $this->app->log::ok($this->req->msg_id);
        /*
         * text msg
         */
        if ($this->data->getMsg()->getMsgType() == 0) {
            $response = myApp::pack(1010, [
                'ChatId' => $this->data->getChatId(),
                'stamp' => time(),
                'msg' => $this->data->getMsg(),
            ]);
            foreach ($this->app->server->connections as $fd) {
                if ($fd == $this->fd) {
                    continue;
                }
                $info = $this->app->server->connection_info($fd);
                if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
                    $this->app->server->push($fd, $response, WEBSOCKET_OPCODE_BINARY);
                }
            }
        }
        /*
         * blob msg
         */
        else if ($this->data->getMsg()->getMsgType() == 1) {
            $response = myApp::pack(1010, [
                'ChatId' => $this->data->getChatId(),
                'stamp' => time(),
                'msg' => $this->data->getMsg(),
            ]);
//            $response = myApp::pack(1010, [
//                'ChatId' => $this->data->['ChatId'],
//                'stamp' => time(),
//                'msg' => new \Proto\MessageBody([
//                    'msgID' => $this->data['msg']['msgID'],
//                    'msgType' => $this->data['msg']['msgType'],
//                    'content' => $this->data['msg']['content'],
//                    'stamp' => $this->data['msg']['stamp'],
//                    'from' => $this->data['msg']['from'],
//                ]),
//            ]);
            foreach ($this->app->server->connections as $fd) {
                if ($fd == $this->fd) {
                    continue;
                }
                $info = $this->app->server->connection_info($fd);
                if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
                    $this->app->server->push($fd, $response, WEBSOCKET_OPCODE_BINARY);
                }
            }
        }
    }
}

try{

    $app = myApp::getInstance(APP_PATH.'/conf/application.ini');
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
