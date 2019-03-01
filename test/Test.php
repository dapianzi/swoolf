<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/1
 * Time: 17:29
 */


namespace My;
include '../lib/Swoolf/Loader.php';

//use Throwable;

class MyExtension extends \Exception{}

namespace Other;
function throwE() {
    throw new \My\MyExtension();
}

try{
    $o = new Nemo();
    throwE();
} catch (Exception $e) {
    echo "Exception has been caught.\n";
} catch (\Exception $e) {
    echo "\\Exception has been caught.\n";
}
