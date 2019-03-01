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
    public $app = NULL;
    public $fd = 0;
    public $dispatcher = NULL;
    public $request = NULL;

    public function __construct($app)
    {
        $this->app = $app;
        $this->dispatcher = $app->dispatcher;
        $this->fd = $app->dispatcher->fd;
        $this->request = $app->dispatcher->request;
    }

    public function response($msg_id, $data=null) {
        return $this->app->server->push($this->fd, $this->dispatcher->protocol->encode($msg_id, $data), WEBSOCKET_OPCODE_BINARY);
    }
}