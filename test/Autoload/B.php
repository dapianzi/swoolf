<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/14
 * Time: 17:12
 */
namespace Test\Autoload;
class B implements \Swoolf\Interfaces\FacadeInterface
{
    public function talk() {
        \Swoolf\Log::info('register extra lib dir success!');
    }

    public static function i() {
        return new B();
    }
}