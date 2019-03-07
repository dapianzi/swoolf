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
    public function __construct($a, $b)
    {
        echo sprintf('$a = %s, $b =  %s'.PHP_EOL, $a, $b);;
    }

    public static function i() {
        $args = func_get_args();
        return new TestFacade($args[0], $args[1]);
    }

    public function tell($str) {
        echo sprintf('%s '.PHP_EOL, $str);
    }
}

$app = new \Swoolf\App(APP_PATH . '/conf/application.ini');
$app->log()::ok('Facade success!');
echo $app->utils()::now().PHP_EOL;
$app->loader()::regNamespace('Test', APP_PATH . DS . 'test');
$app->facade('test', 'TestFacade');
$app->test('dapianzi','carl')->tell('Facade success!');