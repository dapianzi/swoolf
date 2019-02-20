<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:24
 */

namespace Swoolf\Protocol;

use \Swoolf;

class MsgPack implements Swoolf\Interfaces\ProtocolInterface
{
    public $msg_key = 'msg_id';

    public function __construct($key='')
    {
        if (!function_exists('msgpack_pack')) {
            throw new \Exception('Use msgpack protocol requied msgpack extension. Check "msgpack.so" exists in your ini file.');
        }
        if (!empty($key)) {
            $this->msg_key = $key;
        }
    }

    public function decode($data) {
        $data = msgpack_unpack($data);
        if (isset($data[$this->msg_key])) {
            $msg_id = $data[$this->msg_key];
            unset($data[$this->msg_key]);
            return new Message($msg_id, $data);
        }

        return FALSE;
    }

    public function encode($msg_id, $data) {
        $data[$this->msg_key] = $msg_id;
        return msgpack_pack($data);
    }

}