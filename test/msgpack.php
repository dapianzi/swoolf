<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20
 * Time: 21:45
 */

$string = '{"time":"下午9:17:44","content":"abdsf"}';
$data = json_decode($string, true);
$buf = msgpack_pack($data);
var_dump(base64_encode($buf));
echo "\n";

print_r(msgpack_unpack($buf));
echo "\n";
print_r(msgpack_unserialize(base64_decode("gqR0aW1lreS4i+WNiDk6MTc6NDSnY29udGVudKVhYmRzZg==")));
echo "\n";