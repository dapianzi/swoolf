<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:24
 */

namespace Swoolf\Interfaces;


interface ProtocolInterface
{
    public function decode($data);

    public function encode($msg_id, $data);
}