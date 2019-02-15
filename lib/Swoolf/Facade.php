<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/15
 * Time: 12:16
 */

namespace Swoolf;


class Facade
{
    public static $facades = [
        'facade' => __CLASS__
    ];

    public static function reg($name, $class) {
        self::$facades[$name] = $class;
    }

    public static function i() {
        return __CLASS__;
    }
}
