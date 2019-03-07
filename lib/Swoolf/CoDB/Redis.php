<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/5
 * Time: 19:23
 */

namespace Swoolf\CoDB;


class Redis extends \Swoole\Coroutine\Redis
{

    public function setArr($key, $value, $ttl=0) {
        return $this->set($key, msgpack_pack($value), $ttl);
    }

    public function getArr($key) {
        return msgpack_unpack($this->get($key));
    }
}