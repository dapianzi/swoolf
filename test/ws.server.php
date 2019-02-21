<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/19
 * Time: 20:37
 */

define('APP_ENV', 'develop');
define('APP_PATH', dirname(dirname(__FILE__)));
require APP_PATH . '/lib/Swoolf/Loader.php';


class myApp extends \Swoolf\App {
    public function onWSMessage($server, $frame)
    {
        \Swoolf\Log::log('Receive msg from fd['.$frame->fd.']:'.$frame->data);
        $data = $this->unpack($frame->data);
        \Swoolf\Log::info($data);
        $data['fd'] = $frame->fd;
        $data['content'] = 'echo:'.$data['content'];
        $response = $this->pack($data);
//        \Swoolf\Log::warm($response);
//        $server->push($fd, $response);
        foreach ($server->connections as $fd) {
            if ($fd == $frame->fd) {
                continue;
            }
            $info = $server->connection_info($fd);
            if ($info['websocket_status'] == WEBSOCKET_STATUS_ACTIVE) {
                $server->push($fd, $response);
            }
        }
    }

    public function pack($data) {
        /*
         * json
         */
//        $protocol = new Swoolf\Protocol\JSON();
//        return $protocol->encode(1002, $data);

        /*
         * msgpack
         */
        $protocol = new Swoolf\Protocol\MsgPack();
//        return base64_encode($protocol->encode(1002, $data));
        return $protocol->encode(1002, $data);
        /*
         * protobuf
         */
    }

    public function unpack($buf) {
        /*
         * json
         */
//        $protocol = new Swoolf\Protocol\JSON();

        /*
         * msgpack
         */
//        $buf = base64_decode($buf);
        $protocol = new Swoolf\Protocol\MsgPack();

        /*
         * protobuf
         */

        $msg = $protocol->decode($buf);
        return $msg->msg_data;
    }

}

myApp::getInstance(APP_PATH.'/conf/application.ini')->run();