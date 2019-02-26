<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/18
 * Time: 21:25
 */
define('APP_PATH', dirname(dirname(__FILE__)));
require APP_PATH . '/lib/Swoolf/Loader.php';

class myHttpApp extends \Swoolf\App {
    function onHttpRequest($request, $response)
    {
        \Swoolf\Log::log($request->server['request_uri']);
        switch ($request->server['request_uri']){
            case '/favicon.ico': {
                $response->status('404');
                break;
            }
            case '/msgpack': {
                $response->end(file_get_contents(APP_PATH . '/test/ws.msgpack.test.html'));
                break;
            }
            case '/json': {
                $response->end(file_get_contents(APP_PATH . '/test/ws.json.test.html'));
                break;
            }
            case '/protobuf': {
                $response->end(file_get_contents(APP_PATH . '/test/ws.protobuf.test.html'));
                break;
            }
            case '/msgpack/pack': {
                $data = $request->post['data'];
                \Swoolf\Log::warm($data);
                $response->end(base64_encode(msgpack_pack(json_decode($data, TRUE))));
                break;
            }
            case '/msgpack/unpack': {
                // msgpack
                $buf = substr($request->rawContent(), 4);
                \Swoolf\Log::warm($buf);
//                $response->end(json_encode(msgpack_unpack($buf)));
                $response->end(json_encode(msgpack_unpack(base64_decode($buf))));
                break;
            }
            default: {
                $response->end(file_get_contents(APP_PATH . '/test/ws.test.html'));
            }
        }
    }
}
$app = new myHttpApp(APP_PATH . '/conf/application.ini');
$app->serverConf([
    'type' => \Swoolf\App::SERVER_TYPE_HTTP,
    'name' => 'Test-http',
    'port' => 8905,
    'settings' => [
        'daemonize' => 1,
        'log_file' => APP_PATH . '/test/http.log',
        'pid_file' => APP_PATH . '/test/http.pid',
        'upload_tmp_dir' => APP_PATH . '/temp/',
        'document_root' => APP_PATH . '/public/',
        "enable_static_handler"=>true,
    ]
]);
$app->run();