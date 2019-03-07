<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:18
 */

namespace Swoolf;


class Dispatcher
{

    const PROTOCOL_TYPE_JSON = 'json';
    const PROTOCOL_TYPE_MSGPACK = 'msgpack';
    const PROTOCOL_TYPE_PROTOBUF = 'protobuf';
    public static $rules;

    public $protocol;
    public $protocolType;
    public $fd;
    public $request;
    public $controller;
    public $action;

    public function __construct($type = ''){
        switch ($type) {
            case self::PROTOCOL_TYPE_JSON:
                $this->protocolType = self::PROTOCOL_TYPE_JSON;
                $this->protocol = new Protocol\JSON();break;
            case self::PROTOCOL_TYPE_MSGPACK:
                $this->protocolType = self::PROTOCOL_TYPE_MSGPACK;
                $this->protocol = new Protocol\MsgPack();break;
            case self::PROTOCOL_TYPE_PROTOBUF:
            default:
            $this->protocolType = self::PROTOCOL_TYPE_PROTOBUF;
                $this->protocol = new Protocol\ProtoBuf();break;
        }
    }

    public static function setRules($rules) {
        $protos = [];
        foreach ($rules as $k=>$v) {
            if (isset($v['proto'])) {
                $protos[$k] = $v['proto'];
            }
            if (isset($v['controller'])) {
                self::$rules[$k] = [
                    $v['controller'],
                    $v['action']
                ];
            }
        }
        Protocol\ProtoBuf::$proto_map = $protos;
    }

    public function dispatch($fd, $data) {
        $this->fd = $fd;
        $this->request = $this->protocol->decode($data);
        if (!$this->request) {
            Log::warm('invalid package '. $data);
            return FALSE;
        }
        if (isset(self::$rules[$this->request->msg_id])) {
            list($this->controller, $this->action) = self::$rules[$this->request->msg_id];
            return TRUE;
        } else {
            Log::warm('Unhandled msg '. $this->request->msg_id);
        }
        return FALSE;
    }
}