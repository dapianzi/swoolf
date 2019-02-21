<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/21
 * Time: 17:29
 */

//$cid = go(function () {
//    echo "co 1 start\n";
//    co::yield();
//    echo "co 1 end\n";
//});
//
//go(function () use ($cid) {
//    echo "co 2 start\n";
//    co::sleep(0.5);
//    co::resume($cid);
//    echo "co 2 end\n";
//});
//
//
//go(function () use ($fp)
//{
//    while($r =  co::fgets($fp)) {
//        echo $r;
//        co::sleep(0.1);
//    };
//});

function first() {
    go(function() {
        $fp = fopen(__DIR__ . "/msgpack.client.php", "r");
//        co::yield();
        while($r =  co::fgets($fp)) {
            echo $r;
//            co::sleep(0.001);
        };
        echo 'first end'.PHP_EOL;
        fclose($fp);
    });
    go(function(){
        $fp = fopen(__DIR__ . "/coroutine.php", "r");
//        co::yield();
        while($r =  co::fgets($fp)) {
            echo $r;
//            co::sleep(0.001);
        };
        echo 'second end'.PHP_EOL;
        fclose($fp);
    });
    echo "first ing".PHP_EOL;
}

function second() {
    echo "another".PHP_EOL;
}

first();
second();