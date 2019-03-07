<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/4
 * Time: 18:16
 */

namespace Swoolf;


class Register
{
    public static $storage;

    public static function get($name) {
        if (isset(self::$storage[$name])) {
            return self::$storage[$name];
        }
        return null;
    }

    public static function set($name, $val) {
        if (!isset(self::$storage[$name])) {
            self::$storage[$name] = $val;
        }
    }

}