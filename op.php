<?php
include 'op1.php';
include 'exc.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Max-Age: 86400");
    http_response_code(204);
    exit(0);
}

// Get request URI (e.g., /op1/op.php)
$uri = $_SERVER['REQUEST_URI'];
$auri = explode('/', $uri);
$op = $auri[1];

$input = file_get_contents("php://input"); // mode: no-cors requis !!!
$args = msgpack_unpack($input);
// $args = json_decode(json: $input, associative: true, flags: JSON_THROW_ON_ERROR );


try {
  $result = op_echo($args);
  $result['time'] = time();

  http_response_code(202);
  header('Content-Type: application/octet-stream');
  $xx = msgpack_pack($result);
  echo $xx;
} catch (Exception $e) {
  $b = null;
  if (!is_a($e, 'AppExc')) {
    $e2 = new AppExc(3001, 'unexpected exception', '', [$e->message], $e->getTrace());
    $b = $e2->serial();
    http_response_code(401);
  } else {
    $b = $e->serial();
    http_response_code(400);
  }
  echo msgpack_pack($b);
}
?>