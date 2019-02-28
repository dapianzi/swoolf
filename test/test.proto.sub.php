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
include_once APP_PATH . DS . 'lib' . DS . 'Swoolf' . DS . 'Loader.php';
//include_once APP_PATH . DS . 'lib' . DS . 'Proto' . DS . 'A.php';
//include_once APP_PATH . DS . 'lib' . DS . 'Proto' . DS . 'B.php';
//include_once APP_PATH . DS . 'lib' . DS . 'GPBMetadata' . DS . 'App.php';

$arr1 = [
    'id' => 1,
    'sub' => [
        'sub_id' => 1001
    ]
];
$arr2 = [
    'sub_id' => 1002
];

// none sub message

$b1 = new \Proto\B($arr2); // message B
$b2 = new \Proto\B(); // another B
$b2->mergeFrom($b1); // it's work.
printf("Message B::sub_id = %d\n", $b2->getSubId()); // ok

//$a1 = new \Proto\A($arr1); // PHP Fatal error:  Cannot merge messages with different class.
$a1 = new \Proto\A(); // message A
$a1->setId($arr1['id']);
$a1->setSub(new \Proto\B($arr1['sub'])); // it's ok.
$a2 = new \Proto\A(); // another A
// merge from $a1
$a2->mergeFrom($a1); // PHP Fatal error:  Cannot merge messages with different class.
printf("Message A::sub::sub_id = %d\n", $a2->getSub()->getSubId());



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