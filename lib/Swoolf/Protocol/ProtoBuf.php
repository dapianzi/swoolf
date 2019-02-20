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
        $proto = new \Swoolf\Protocol\ProtoBufMessage($msg_id);
        if (!$proto) {
//            $this->err = 'No msg id matched.';
            return FALSE;
        }
        try {
            $msg_obj = new $proto->getProto();
            $msg_obj->mergeFromString(pack('a*', $body[1]));
        } catch (\Exception $e) {
//            $this->err = $e->getMessage();
            return FALSE;
            // handle invalid msg
//            throw new MessageParseException('Invalid message');
        }
        return new Message($msg_id, json_decode($msg_obj->serializeToJsonString(), true));

    }

    public function encode($msg_id, $data) {
        $proto = new \Swoolf\Protocol\ProtoBufMessage($msg_id);
        if (!$proto) {
//            $this->err = 'No msg id matched.';
            return FALSE;
        }
        $msg_obj = new $proto->getProto();
        $msg_obj->mergeFromArray($data);
        $body = $msg_obj->serializeToString();
        return pack('N', $msg_id) . $body;
    }
}