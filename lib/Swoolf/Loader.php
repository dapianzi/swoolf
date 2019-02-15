<?php
namespace Swoolf;
/**
 *
 * @Author: carl
 * @Since: 2019/2/9 ${time}
 * Created by PhpStorm.
 */
class Loader
{

    private static $root = [];

    public static function autoLoad($class) {
        $dir = explode('\\', $class);
        $cls = array_pop($dir);
        if (count($dir) > 0 && isset(self::$root[$dir[0]])) {
            $ext = array_shift($dir);
            $lib = self::$root[$ext];
        } else {
            $lib = dirname(dirname(__FILE__));
        }
        $file = $lib . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $dir) . DIRECTORY_SEPARATOR . $cls . '.php';
        if (file_exists($file)) {
            include_once $file;
        } else {
            throw new LoadException('Load class '.$class.' failed. file "'.$file.'" not exists.');
        }
    }

    public static function regNamespace($namespace, $dir) {
        $namespace = trim($namespace, '\\');
        self::$root[$namespace] = $dir;
    }

    public static function i() {
        return __CLASS__;
    }

}
class LoadException extends \Exception {}
spl_autoload_register(['\\Swoolf\\Loader', 'autoload']);
//spl_autoload_register('\\Swoolf\\Loader::autoload');