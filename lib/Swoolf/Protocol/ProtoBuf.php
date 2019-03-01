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

    public static $proto_map = [];

    public function __construct(){}

    public function decode($buf) {
        /**
         * TODO 数据传输协议
         */
        $data = unpack('Nmsgid', $buf);
        $msg_id = $data['msgid'];
        $body = unpack('a*', $buf, 4);
        $proto = $this->getProto($msg_id);
        if ($proto) {
            try {
//                $proto->mergeFromString($body[1]);
                $proto->mergeFromString(pack('a*', $body[1]));
                return new Swoolf\RequestMsg($msg_id, $proto);
            } catch (\Exception $e) {
                Swoolf\Log::err('[Protobuf Error]: '. $e->getMessage());
                unset($e);
                return NULL;
            }
        } else {
            return NULL;
        }
    }

    public function encode($msg_id, $data) {
        try {
            if (is_array($data) || is_null($data)) {
                $proto = $this->getProto($msg_id, $data);
            } else {
                $proto = $this->getProto($msg_id);
                $proto->mergeFrom($data);
            }
            if (!$proto) {
                return FALSE;
            }
        } catch (\Exception $e) {
            Swoolf\Log::err('[Protobuf Error]: '. $e->getMessage());
            unset($e);
            return FALSE;
        }
        $body = $proto->serializeToString();
        unset($proto);
        return pack('N', $msg_id) . $body;
    }

    public function setProtoMap($proto) {
        foreach ($proto as $k=>$v) {
            self::$proto_map[$k] = $v;
        }
    }

    protected function getProto($id, $data=NULL) {
        if(!empty(self::$proto_map[$id])) {
            $cls = '\\Proto\\' . self::$proto_map[$id];
            return new $cls($data);
        } else {
            Swoolf\Log::warm('Unrecognised message '.$id);
            return FALSE;
        }
    }
}