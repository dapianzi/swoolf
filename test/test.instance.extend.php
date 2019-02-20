<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20
 * Time: 12:12
 */

class A{
    static $INSTANCE = [];
    public $name = 'A';
    public function talk() {
        echo $this->name;
        $this->event();
    }

    public function event() {
        echo 'This is A';
    }

    /**
     * get self instance.
     * @return null|A
     */
    public static function getInstance() {
        $cls = get_called_class();
        if (!isset(self::$INSTANCE[$cls])) {
            $argv = func_get_args();
            self::$INSTANCE[$cls] = new $cls($argv);
        }
        return self::$INSTANCE[$cls];
    }
}

class B extends A {
    public $name = 'B';
    public function event() {
        echo 'This is B';
    }
}

(new B())->talk();
B::getInstance()->talk();

