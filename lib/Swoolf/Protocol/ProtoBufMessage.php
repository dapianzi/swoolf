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

    public $msg = 0;
    public $proto = null;
    public static $msg_proto = [];

    public function __construct($msg_id){
        if (isset(self::$msg_proto[$msg_id])) {
            $this->msg = $msg_id;
            $this->proto = self::$msg_proto[$msg_id];
        }
    }

    public static function setProto($msg_proto) {
        foreach ($msg_proto as $k=>$v) {
            self::$msg_proto[$k] = $v;
//            if (isset($v['response_id'])) {
//                self::$msg_proto[$v['response_id']] = $v['response_proto'];
//            }
        }
    }

    public function getId() {
        if(!$this->msg) {
            return $this->msg;
        }
    }

    public function getProto($data=NULL) {
        if(!empty($this->proto)) {
            $cls = '\\Proto\\'.$this->proto;
            return new $cls($data);
        }
    }
}