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

    /**
     * async mode + coroutine
     */
    public function run() {
        $client = new \Swoole\Http\Client('127.0.0.1', 8907);
        $client->set([

        ]);
        $o = $this;
        $client->on('message', function($cli, $frame) use ($o) {
            Swoolf\Log::ok($frame);
            $data = $o->unpack($frame->data);
            Swoolf\Log::info(sprintf('[%d]: %s', $data['fd'], $data['content']));
        });
        $client->upgrade('/', function($cli) use ($o) {
            go(function() use ($o, $cli){
                $fp = fopen('php://stdin', 'r');
                while(TRUE) {
                    $msg = trim(fgets($fp));
                    if ($msg == 'quit' || $msg == 'bye') {
                        Swoolf\Log::warm('Bye');
                        break;
                    } else if ($msg != "") {
                        $cli->push($o->pack($msg));
                        // 设置0.1s超时时间
                    }
                    // 获取屏幕输入会阻塞进程，因此借用协程切换让客户端收取消息，然后再切换回来
                    co::sleep(0.001);
                }
                fclose($fp);
                $cli->close();
            });
        });
    }

    /**
     * coroutine mode
     */
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
                    $msg = trim(fgets($fp));
                    if ($msg == 'quit' || $msg == 'bye') {
                        Swoolf\Log::warm('Bye');
                        break;
                    } else if ($msg != "") {
                        $client->push($o->pack($msg));
                    }
                    // 设置0.1s超时时间，触发协程切换，使进程重新获取屏幕输入
                    while($frame = $client->recv(0.1)) {
                        $data = $o->unpack($frame->data);
                        Swoolf\Log::info(sprintf('[%d]: %s', $data['fd'], $data['content']));
                    }
                    // print
                    //co::sleep(0.001);
                }
                fclose($fp);
            }
        });
    }

    public function pack($msg) {
        return base64_encode(msgpack_pack([
            'msg_id' => 1002,
            'time' => date('A H:i:s'),
            'content' => $msg,
        ]));
    }

    public function unpack($str) {
        return msgpack_unpack(base64_decode($str));
    }
}

$client = new msgpackClient();
$client->run();