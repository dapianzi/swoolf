<?php
/**
 *
 * @Author: carl
 * @Since: 2019/2/9 ${time}
 * Created by PhpStorm.
 */
namespace Swoolf\Server;

use \Swoolf;

class WebSocket extends \Swoole\WebSocket\Server {
    public function __construct(string $host, int $port, $mode = SWOOLE_PROCESS, $sock_type = SWOOLE_SOCK_TCP)
    {
        parent::__construct($host, $port, $mode, $sock_type);
    }
}