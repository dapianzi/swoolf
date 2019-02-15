<?php
/**
 *
 * @Author: carl
 * @Since: 2019/2/9 ${time}
 * Created by PhpStorm.
 */
namespace Swoolf\Server;

class Http {

    private $server;
    private $app;

    public function __construct($setting) {
        /*
         * default server setting
         */
        $default_setting = [
            // bind ip address
            'host' => '0.0.0.0',
            // bind server port
            'port' => 232701,
            // swoole mode
            'mode' => SWOOLE_PROCESS,
            // socket type
            'socket_type' => SWOOLE_SOCK_TCP,
            // swoole server settings
            'setting' => [

            ]
        ];
        $this->server = new \Swoole\Http\Server($this->host, $this->port, $this->mode, $this->socket_type);
        $setting = array_merge($default_setting, $setting);
        $this->server->set($setting);
        $this->bind();
    }

    public function bind() {
        /*
         * master & manager process start.
         */
        $this->server->on('start', [$this, 'onStart']);
        /*
         * master & manager process shutdown.
         */
        $this->server->on('shutdown', [$this, 'onShutdown']);
        /*
         * worker & task process start.
         */
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        /*
         * worker & task process stop.
         */
        $this->server->on('workerStop', [$this, 'onWorkerStop']);
        /*
         * master & manager process start.
         */
        $this->server->on('workerExit', [$this, 'onWorkerExit']);
        /*
         * master & manager process start.
         */
        $this->server->on('request', [$this, 'onRequest']);
        /*
         * master & manager process start.
         */
        $this->server->on('receive', [$this, 'onStart']);
    }

    public function onStart() {

    }

    public function start() {
        $this->server->start();
    }

    public static function getInstance() {

    }
}