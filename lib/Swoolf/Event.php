<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/15
 * Time: 11:51
 */

namespace Swoolf;

use Throwable;

class Event implements Interfaces\FacadeInterface
{

    public static $events;

    /**
     * @param $event
     * @param $func
     */
    public static function add($event, $func) {
        self::$events[$event][] = $func;
    }

    /**
     * @param $event
     * @param $func
     */
    public static function remove($event, $func) {
        if (isset(self::$events[$event])) {
            $idx = array_search($func, self::$events[$event]);
            if (false !== $idx) {
                unset(self::$events[$event][$idx]);
            }
        }
    }

    /**
     * @throws EventException
     */
    public static function emit() {
        $argv = func_get_args();
        $event = array_shift($argv);
        if (isset(self::$events[$event])) {
//            throw new EventException('Unknow event: '. $event);
            foreach (self::$events[$event] as $e) {
                call_user_func_array($e, $argv);
            }
        }
    }

    public static function i() {
        return __CLASS__;
    }
}

class EventException extends \Exception {
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct('[Event] '.$message, $code, $previous);
    }
}