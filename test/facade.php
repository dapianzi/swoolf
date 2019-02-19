<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/15
 * Time: 11:50
 */
define('APP_PATH', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);
include_once APP_PATH . DS . 'lib' . DS . 'Swoolf' . DS . 'Loader.php';
class TestFacade implements \Swoolf\Interfaces\FacadeInterface {
    public $conf;
    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    public static function i() {
        return new TestFacade('dapianzi');
    }

    public function tell($str) {
        echo sprintf('%s %s', $this->conf, $str);
    }
}

$app = new \Swoolf\App(APP_PATH . '/conf/application.ini');
$app->log::ok('Facade success!');
echo $app->utils::now().PHP_EOL;
$app->loader::regNamespace('Test', APP_PATH . DS . 'test');
$app->facade::reg('test', '\Test\Autoload\B');
$app->test->talk('Facade success!');