<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 17:53
 */

namespace Swoolf;

use Throwable;

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Loader.php';
class App
{

    public static $INSTANCE = NULL;
    public static $DEBUG = TRUE;

    public $name;

    public function __construct($ini)
    {

        // parse ini config
        $this->name = $ini['name'];
        // global config

        // log config
        Log::setDebug(self::$DEBUG);
        // server config

        // register facades
        $this->facade::reg('log', __NAMESPACE__.'\Log');
        $this->facade::reg('utils', __NAMESPACE__.'\Utils');
        $this->facade::reg('event', __NAMESPACE__.'\Event');
        $this->facade::reg('loader', __NAMESPACE__.'\Loader');

        self::$INSTANCE = $this;
    }


    public function run() {
        try {
            $this->server->start();
        } catch (\Exception $e) {
            Log::err($e->getMessage().PHP_EOL.$e->getTraceAsString());
        }
    }


    public function __get($name)
    {
        if (isset(Facade::$facades[$name])) {
            return Facade::$facades[$name]::i();
        } else {
            throw new SwoolfException('unregister facade:'. $name);
        }
    }

    public static function getInstance() {
        if (is_null(self::$INSTANCE)) {
            $argv = func_get_args();
            self::$INSTANCE = new App($argv[0]);
        }
        return self::$INSTANCE;
    }

}

class SwoolfException extends \Exception {
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct('[Swoolf] '.$message, $code, $previous);
    }
}