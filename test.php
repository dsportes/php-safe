<?php
// Source - https://stackoverflow.com/a
// Posted by s3c, modified by community. See post 'Timeline' for change history
// Retrieved 2026-01-26, License - CC BY-SA 4.0

/**
 * receive_body___send_response.php
 */

/* Get content type */
$contentType = trim($_SERVER["Content-Type"] ?? ''); // PHP 8+
// Otherwise:
// $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

/* Send error to Fetch API, if unexpected content type 
if ($contentType !== "application/json")
  die(json_encode([
    'value' => 0,
    'error' => 'Content-Type is not set as "application/json"',
    'data' => null,
  ]));
  */

/* Receive the RAW post data. */
$content = trim(file_get_contents("php://input"));

/* $decoded can be used the same as you would use $_POST in $.ajax */
$decoded = json_decode($content, true);

/* Send error to Fetch API, if JSON is broken */
if(! is_array($decoded))
  die(json_encode([
    'value' => 0,
    'error' => 'Received JSON is improperly formatted',
    'data' => null,
  ]));

/* NOTE: For some reason I had to add the next line as well at times, but it hadn't happen for a while now. Not sure what went on */
// $decoded = json_decode($decoded, true);

/* Do something with received data and include it in response */
// dumb e.g.
$response = $decoded['key2'] + 1; // 3

/* Perhaps database manipulation here? */
// query, etc.

http_response_code(200);
/* Send success to fetch API */
$resp = json_encode([
  'value' => 1,
  'error' => null,
  'data' => $response, // or ?array of data ($response) you wish to send back to JS
]);
echo $resp;
?>