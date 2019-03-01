<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 16:40
 */

namespace Swoolf;


class RequestMsg
{

    public $msg_id;
    public $msg_data;

    public function __construct($id, $data)
    {
        $this->msg_id = $id;
        $this->msg_data = $data;
    }

}