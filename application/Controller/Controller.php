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
        $this->app->server->push($this->fd, $this->app->dispatcher->protocol->encode($msg_id, $data), WEBSOCKET_OPCODE_BINARY);
    }
}