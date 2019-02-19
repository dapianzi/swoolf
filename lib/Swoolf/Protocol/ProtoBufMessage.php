<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:56
 */

namespace Swoolf\Protocol;


class ProtoBufMessage
{

    public $msg = null;
    public static $msg_proto = [];

    public function __construct($msg_id){
        if (isset(self::$msg_proto[$msg_id])) {
            $this->msg = self::$msg_proto[$msg_id];
        }
    }

    public static function setProto($msg_proto) {
        foreach ($msg_proto as $v) {
            self::$msg_proto[$v['id']] = $v;
            if (isset($v['response_id'])) {
                self::$msg_proto[$v['response_id']] = $v['response_proto'];
            }
        }
    }

    public function getId() {
        if(!is_null($this->msg)) {
            return $this->msg['id'];
        }
    }

    public function getProto() {
        if(!is_null($this->msg)) {
            return $this->msg['proto'];
        }
    }

    public function getResponseMessageId(){
        if(!is_null($this->msg)) {
            return $this->msg['response_id'];
        }
    }

    public function getResponseMessageProto(){
        if(!is_null($this->msg)) {
            return $this->msg['response_proto'];
        }
    }
}