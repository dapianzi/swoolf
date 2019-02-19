<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 17:52
 */

namespace Swoolf;


class Log implements Interfaces\FacadeInterface
{

    static public $LOG_FILE = '/tmp/swoolf.log';

    static public $DEBUG = TRUE;

    static public function setDebug($debug) {
        self::$DEBUG = $debug;
    }

    static public function setLogFile($filename) {
        if (is_writable(dirname($filename))) {
            self::$LOG_FILE = $filename;
        } else {
            self::err('filename: ' . $filename . ' can not be written.');
        }
    }

    /**
     * @param $var
     * @param bool $flag
     * @return bool
     */
    static public function err($var, $flag=FALSE) {
        return self::log($var, $flag, 'r');
    }

    /**
     * @param $var
     * @param bool $flag
     * @return bool
     */
    static public function warm($var, $flag=FALSE) {
        return self::log($var, $flag, 'y');
    }

    /**
     * @param $var
     * @param bool $flag
     * @return bool
     */
    static public function info($var, $flag=FALSE) {
        return self::log($var, $flag, 'b');
    }

    /**
     * @param $var
     * @param bool $flag
     * @return bool
     */
    static public function ok($var, $flag=FALSE) {
        return self::log($var, $flag, 'g');
    }

    /**
     * * QUOTE:
     * 字背景颜色范围: 40--49                   字颜色: 30--39
     *      40: 黑                           30: 黑
     *      41: 红                           31: 红
     *      42: 绿                           32: 绿
     *      43: 黄                           33: 黄
     *      44: 蓝                           34: 蓝
     *      45: 紫                           35: 紫
     *      46: 深绿                         36: 深绿
     *      47: 白色                         37: 白色
     *
     * @param $var
     * @param string $color
     * @param bool $flag
     * @return bool
     */
    static public function log($var, $flag=FALSE, $color='') {

        $str = is_object($var)||is_array($var) ? json_encode($var) : strval($var);
        switch ($color) {
            case 'r':
            case 'red':
                $color = '1;31';
                break;
            case 'g':
            case 'green':
                $color = '1;32';
                break;
            case 'b':
            case 'blue':
                $color = '0;34';
                break;
            case 'y':
            case 'yellow':
                $color = '0;33';
                break;
            default:
                $color = FALSE;
        }
        if ($color) {
            $msg = sprintf("\033[%sm[%s] %s\033[0m".PHP_EOL, $color, date('Y-m-d H:i:s'), $str);
        } else {
            $msg = sprintf("[%s] %s".PHP_EOL, date('Y-m-d H:i:s'), $str);
        }
        if ($flag) {
            echo $msg;
        }
        if (!self::$DEBUG) {
            return error_log($msg, 3, self::$LOG_FILE);
        } else if (!$flag) {
            echo $msg;
        }
    }

    public static function i() {
        return __CLASS__;
    }

}

class LogException extends \Exception {}