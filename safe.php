<?php
include 'ops.php';
include 'exc.php';
include 'db.php';

// Gestion de CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("Access-Control-Max-Age: 86400");
  http_response_code(204);
  exit(0);
}
header('Content-Type: application/octet-stream');

try {
  $uri = $_SERVER['REQUEST_URI'];
  $i = strpos($uri, '?');
  $opName = substr($uri, $i + 1);

  $mysqli = new mysqli('localhost', 'Daniel', 'Ds3542mysql', 'mysafe');                              
  if (!$mysqli) throw new AppExc(2001, 'DB connexion failure', $opName, ['mysafe@localhost'], []);

  $input = file_get_contents("php://input"); // DOIT accepter CORS
  $args = msgpack_unpack($input);

  $large = file_get_contents('./doc.md', FILE_USE_INCLUDE_PATH);

  test2($mysqli, $large);
  $xx = test3($mysqli);
  $result = null;

  switch ($opName) {
    case 'echo': 
      $result = op_echo($args); 
      break;
    default: 
      throw new AppExc(1002, 'unknown operation', $opName, [$opName], []);
  }

  http_response_code(200);
  echo msgpack_pack($result);

} catch (Exception $e) {
  if (!is_a($e, 'AppExc')) {
    http_response_code(401);
    $e2 = new AppExc(3001, 'unexpected exception', $opName, [$e->getMessage()], $e->getTrace());
    echo $e2->serial();
  } else {
    http_response_code(400);
    echo $e->serial();
  }
}

?>