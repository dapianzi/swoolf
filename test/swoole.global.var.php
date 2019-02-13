<?php
/**
 *
 * @Author: carl
 * @Since: 2019/2/9 ${time}
 * Created by PhpStorm.
 */

$global_var = [
    ['name' => 'original']
];

$serv = new \Swoole\Http\Server();
$serv->set([
    'worker_num' => 4,
]);

$serv->on('Request', function($req, $res) use($global_var){
    $global_var[] = [
        'name' => rand(100, 999),
    ];
    print_r($global_var);
    $res->end('ok');
});