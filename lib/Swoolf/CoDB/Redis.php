<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/5
 * Time: 19:23
 */

namespace Swoolf\CoDB;


class Redis
{
    private $db;
    private $conf;

    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->db = new \Swoole\Coroutine\Redis();
        $this->db->setOptions([
            'connect_timeout' => 1, // 连接的超时时间, 默认为全局的协程socket_connect_timeout(1秒)
            'timeout' => 3,// 超时时间, 默认为全局的协程socket_timeout(-1, 永不超时)
            'serialize' => false, // 自动序列化, 默认关闭
            'reconnect' => 1, //
        ]);
        $this->db->connect($this->conf['host'], $this->conf['port']);
    }
}