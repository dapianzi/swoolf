<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 20:29
 */

namespace App\Controller;


class MessageController extends Controller
{

    public function SendMessageAction() {
        $msg = $this->request->msg_data->getMsg();
        $this->app->log::warm($msg->getMsgID());
        $this->response(1008, [
            'msgID' => $msg->getMsgID()
        ]);
        // save message to db;
        $this->app->log::ok($this->request->msg_id);
        // broadcast message
        // todo should done in a task

        $ChatId = $this->request->msg_data->getChatId();
        $msgType = $msg->getMsgType();
        if ($msgType == 0) {
            // text msg
            $response = $this->app->dispatcher->protocol->encode(1010, [
                'ChatId' => $ChatId,
                'msg' => $msg,
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
        } else if ($msgType == 1) {
            $response = $this->app->dispatcher->protocol->encode(1010, [
                'ChatId' => $ChatId,
                'msg' => $msg,
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
    }

}