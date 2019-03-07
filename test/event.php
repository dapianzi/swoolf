<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/15
 * Time: 15:46
 */

define('APP_PATH', dirname(dirname(__FILE__)));

include APP_PATH . DIRECTORY_SEPARATOR.'lib/Swoolf/Loader.php';
class EventTest {

    public function run($data) {
        $app = \Swoolf\App::getInstance();
        $app->event()::emit('before', $data);
        // do something with data
        $data = json_encode($data);
        $app->event()::emit('after', $data);
    }

    public function cls_event($data) {
        \Swoolf\Log::info('after:' . $data);
    }
}

$app = new \Swoolf\App(APP_PATH . '/conf/application.ini');

function fn_event($data) {
    \Swoolf\Log::warm('this method should already been removed.');
}

$event_test = new EventTest();
$app->event()::add('before', function($data) use ($app) {
    $app->log()::log('app name before action');
    $app->log()::ok($data);
});
// anonymous function can't be removed?
//$app->event::remove('before', function($data) use ($app) {
//    $app->log::log('app name before action:'.$app->name);
//    $app->log::ok($data);
//});
$app->event::add('after', [$event_test, 'cls_event']);
// class method remove successfully.
$app->event::remove('after', [$event_test, 'cls_event']);

$app->event::add('before', 'fn_event');
// normal function remove successfully
//$app->event::remove('before', 'fn_event');
$event_test->run([
    'name' => 'test event',
    'time' => \Swoolf\Utils::now(),
    'result' => 'success',
]);