<?php
/**
 *
 * @Author: carl
 * @Since: 2019/2/9 ${time}
 * Created by PhpStorm.
 */

$global_var = ['times' => 0];

$serv = new \Swoole\Http\Server('0.0.0.0', 9502);
$serv->set([
    'worker_num' => 4,
]);

$serv->on('Request', function($req, $res) use($global_var){
    if ($req->server['request_uri'] == '/favicon.ico') {
        $res->status('404');
//        $res->end('');
    }
    /*
     * Swoole启动后，全局变量会拷贝至子进程
     * 对全局变量的修改，在不同的进程不一样
     * 因此，对于游戏中的常驻内存数据，需要借助 Redis 等动态管理
     */
    $global_var['times']++;
    printf("[fd %d] your order: %d\n", $req->fd, $global_var['times']);
    $res->end('ok');
});
$serv->start();