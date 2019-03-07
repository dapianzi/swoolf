<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 19:41
 */

namespace App\Controller;


class Controller
{
    public $fd = 0;
    public $app = NULL;
    public $request = NULL;

    public function __construct($fd, $request)
    {
        $this->fd = $fd;
        $this->request = $request;
        $this->app = \Swoolf\App::getInstance();
    }

    public function response($msg_id, $data=null) {
        $this->getServer()->push($this->fd, $this->encode($msg_id, $data), WEBSOCKET_OPCODE_BINARY);
    }

    public function encode($msg_id, $data) {
        return $this->app->dispatcher->protocol->encode($msg_id, $data);
    }

    public function getServer() {
        return $this->app->server;
    }

    public function getRedis() {
        return $this->app->redis();
    }

    public function getDB($name='') {
        if (empty($name)) {
            return $this->app->db;
        } else {
            return $this->app->db[$name];
        }
    }

    public function getData() {
        return $this->request->msg_data;
    }

    public function getMsgId() {
        return $this->request->msg_id;
    }
}