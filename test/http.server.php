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
        if ($request->server['request_uri'] == 'favicon.ico') {
            $response->status('404');
        }
        $response->end(file_get_contents('./ws.test.html'));
    }
}
$app = new myHttpApp(APP_PATH . '/conf/application.ini');
$app->serverConf([
    'type' => \Swoolf\App::SERVER_TYPE_HTTP,
    'name' => 'Test-http',
    'port' => 8905,
    'settings' => [
        'daemonize' => 0,
        'log_file' => APP_PATH . '/test/http.log',
        'pid_file' => APP_PATH . '/test/http.pid',
    ]
]);
$app->run();