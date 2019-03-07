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
        $msg = $this->getData()->getMsg();
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
        $this->getServer()->task($task_data);
        $this->response(1008, [
            'msgID' => $msg->getMsgID()
        ]);
        // broadcast message
        $ChatId = $this->getData()->getChatId();
//        $msgType = $msg->getMsgType();
        $response = $this->encode(1010, [
            'ChatId' => $ChatId,
            'msg' => $msg,
        ]);
//        if ($msgType == 0) {
//            // text msg
//
//        } else if ($msgType == 1) {
//
//        }
        $this->getServer()->task(['task_id'=>1000, 'fd'=>$this->fd, 'response'=>$response]);
    }

}