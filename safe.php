<?php
echo 'safe.php started';
$userData = ['user_id' => 456, 'username' => 'bob', 'is_admin' => false];
$packed = msgpack_pack($userData);
//// $packed is now a binary string representing the array
$unpacked = msgpack_unpack($packed);
echo $unpacked['user_id'];
echo $userData['user_id'];

// phpinfo();
?>
