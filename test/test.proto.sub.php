<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 11:09
 */

define('APP_PATH', dirname(dirname(__FILE__)));
define('DS', DIRECTORY_SEPARATOR);
// loader
//include_once APP_PATH . DS . 'lib' . DS . 'Swoolf' . DS . 'Loader.php';
include_once APP_PATH . DS . 'lib' . DS . 'Proto' . DS . 'A.php';
include_once APP_PATH . DS . 'lib' . DS . 'Proto' . DS . 'B.php';
include_once APP_PATH . DS . 'lib' . DS . 'GPBMetadata' . DS . 'App.php';

$arr = [
    'id' => 1,
    'type' => 0,
    'sub' => [
        'name' => 'sub msg'
    ]
];

$t1 = new \Proto\A($arr); // PHP Fatal error:  Cannot merge messages with different class.

//$t2 = new \Proto\A();
//$t2->mergeFromJsonString(json_encode($arr, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
//
////$t3 = new \Proto\A();
////$t3->setSub(new \Proto\B($arr['sub']));
////$t3->setType($arr['type']);
////$t3->setId($arr['id']);
//
////$buf = $t1->serializeToString();
//$buf = $t2->serializeToString();
////$buf = $t3->serializeToString();
//$t = new \Proto\A();
//$t->mergeFromString($buf);
//\Swoolf\Log::ok('id = '.$t->getId());
//\Swoolf\Log::ok('type = '.$t->getType());
//\Swoolf\Log::ok('sub.name = '.$t->getSub()->getName());
//\Swoolf\Log::ok('msg = '.$t->serializeToJsonString());