<?php
// header('Content-Type: text/html; charset=UTF-8');
echo 'BLU BL safe.php started bla bla';
$userData = ['user_id' => 456, 'username' => 'bob', 'is_admin' => false];
$packed = msgpack_pack($userData);
//// $packed is now a binary string representing the array
$unpacked = msgpack_unpack($packed);
echo $unpacked['user_id'] . '<br>';
echo $userData['user_id'] . '<br>';
echo time() . '<br>';

http_response_code(201);
// header('Content-Type: application/octet-stream');
// phpinfo();
?>
