<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20
 * Time: 19:57
 */

define ('APP_PATH', dirname(dirname(__FILE__)));
require APP_PATH.'/lib/Swoolf/Loader.php';

class msgpackClient {
    public $client;
    public function __construct()
    {

    }

    public function run() {
        $client = new \Swoole\Http\Client('127.0.0.1', 8907);
        $client->set([

        ]);

    }

    public function start() {
        $o = $this;
        go(function () use ($o) {
            $client = new \Swoole\Coroutine\Http\Client('127.0.0.1', 8907);
            $o->client = $client;
            $ret = $client->upgrade("/");
            if ($ret) {
                // 监听输入
                $fp = fopen('php://stdin', 'r');
                while(TRUE) {
                    $msg = fgets($fp);
                    if ($msg != "\n") {
                        $client->push($o->pack($msg));
                        // 设置0.1s超时时间
                        while($data = $client->recv(0.1)) {
                            Swoolf\Log::info($o->unpack($data));
                        }
                    } else {
                        Swoolf\Log::warm('Bye');
                        break;
                    }
                    // print
                    //co::sleep(0.001);
                }
                fclose($fp);
            }
        });
    }

    public function pack($msg) {
        return msgpack_pack([
            'msg_id' => 1002,
            'time' => date('H:i:s'),
            'content' => $msg,
        ]);
    }

    public function unpack($str) {
        return msgpack_unpack($str);
    }
}

$client = new msgpackClient();
$client->start();