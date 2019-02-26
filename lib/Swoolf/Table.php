<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/25
 * Time: 17:53
 */

namespace Swoolf;


class Table extends \Swoole\Table implements Interfaces\FacadeInterface
{
    public static $instance = null;
    public function __construct(int $size)
    {
        parent::__construct($size);
        self::$instance = $this;
    }


    public static function i() {
        if (!self::$instance) {
            $argv = func_get_args();
            self::$instance = new Table($argv[0]);
        }
        return self::$instance;
    }
}