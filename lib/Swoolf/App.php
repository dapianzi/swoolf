<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 17:53
 */

namespace Swoolf;

include dirname(__FILE__).DIRECTORY_SEPARATOR.'Loader.php';
class App
{

    public static $DEBUG = TRUE;

    public function __construct($ini)
    {

        // parse ini config

        // global config

        // log config
        Log::setDebug(self::$DEBUG);
        // server config

        // register facades
        $this->facade::reg('log', __NAMESPACE__.'\Log');
        $this->facade::reg('utils', __NAMESPACE__.'\Utils');
        $this->facade::reg('loader', __NAMESPACE__.'\Loader');

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

}

class SwoolfException extends \Exception {}