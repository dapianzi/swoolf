<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:24
 */
namespace Swoolf\Protocol;

use \Swoolf;

class JSON implements Swoolf\Interfaces\ProtocolInterface
{

    public $msg_key = 'msg_id';

    public function __construct($key='')
    {
        if (!empty($key)) {
            $this->msg_key = $key;
        }
    }

    public function encode($msg_id, $data) {
        $data[$this->msg_key] = $msg_id;
        return json_encode($data);
    }

    public function decode($data) {
        $data = json_decode($data, TRUE);
        if (isset($data[$this->msg_key])) {
            $msg_id = $data[$this->msg_key];
            unset($data[$this->msg_key]);
            return new Swoolf\RequestMsg($msg_id, $data);
        }

        return FALSE;
    }
}