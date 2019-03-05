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
        // save to db
        $task_data = [
            'task_id' => 1001,
            'data' => [
                'msgID' => $msg->getMsgID(),
                'msgStamp' => $msg->getStamp(),
                'msgType' => $msg->getMsgType(),
                'msgContent' => $msg->getContent(),
                'msgFrom' => $msg->getFrom(),
            ]
        ];
        $this->app->server->task($task_data);
        $this->response(1008, [
            'msgID' => $msg->getMsgID()
        ]);
        // broadcast message
        $ChatId = $this->request->msg_data->getChatId();
        $msgType = $msg->getMsgType();
        if ($msgType == 0) {
            // text msg
            $response = $this->app->dispatcher->protocol->encode(1010, [
                'ChatId' => $ChatId,
                'msg' => $msg,
            ]);

        } else if ($msgType == 1) {
            $response = $this->app->dispatcher->protocol->encode(1010, [
                'ChatId' => $ChatId,
                'msg' => $msg,
            ]);
        }
        $this->app->server->task(['task_id'=>1000, 'fd'=>$this->fd, 'response'=>$response]);
    }

}