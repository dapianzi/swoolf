<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/5
 * Time: 19:23
 */

namespace Swoolf\DB;


class RedisDriver
{
    private static $instance = [];
    private $master;
    private $slave;

    public function __construct($conf)
    {
        $this->master = new \Redis($conf['host'], $conf['port'], $conf['timeout']);
        if (isset($conf['slave'])) {
            foreach ($conf['slave'] as $s) {
                $this->slave[] = new \Redis($s['host'], $s['port'], $s['timeout']);
            }
        } else {
            $this->slave[] = $this->master;
        }
    }

    public function db() {
        return $this->master;
    }

    public function master() {
        return $this->master;
    }

    public function salve() {
        return $this->slave[rand(0, $this->salveCount()-1)];
    }

    public function salveCount() {
        return count($this->slave);
    }

}