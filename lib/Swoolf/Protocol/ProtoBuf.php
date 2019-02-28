<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:24
 */

namespace Swoolf\Protocol;

use \Swoolf;

class ProtoBuf implements Swoolf\Interfaces\ProtocolInterface
{

    public function __construct(){
    }


    public function decode($buf) {
        $data = unpack('Nmsgid', $buf);
        $msg_id = $data['msgid'];
        $body = unpack('a*', $buf, 4);
        \Swoolf\Log::info($body);
        $proto = new \Swoolf\Protocol\ProtoBufMessage($msg_id);
        if (!$proto) {
//            $this->err = 'No msg id matched.';
            \Swoolf\Log::err('unknow message id '.$msg_id);
            return FALSE;
        }
        try {

            $msg_obj = $proto->getProto();
            // TODO user swoole buffer
            $msg_obj->mergeFromString(pack('a*', $body[1]));
        } catch (\Exception $e) {
            \Swoolf\Log::err($e->getMessage());
            return FALSE;
//            throw $e;
            // handle invalid msg
//            throw new MessageParseException('Invalid message');
        }
        return new Message($msg_id, $msg_obj);
    }

    public function encode($msg_id, $data) {
        $proto = new \Swoolf\Protocol\ProtoBufMessage($msg_id);
        if (!$proto) {
//            $this->err = 'No msg id matched.';
            return FALSE;
        }
        if (is_array($data)) {
            $msg_obj = $proto->getProto($data);
//            $msg_obj->mergeFromJsonString(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
//            $msg_obj->mergeFromJsonString(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        } else {
            $msg_obj = $proto->getProto();
            $msg_obj->mergeFrom($data);
        }

        $body = $msg_obj->serializeToString();
        // TODO user swoole buffer
        return pack('N', $msg_id) . $body;
    }
}