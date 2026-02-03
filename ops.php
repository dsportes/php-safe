<?php

function op_echo() {
  global $mysqli, $result, $opName, $args;
  $result['echo'] = $args;
}

function u8ToB64($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function op_hash() {
  global $result, $args;
  $h = hash('sha256', $args['bin'], true);
  $result['sha'] = u8ToB64($h);
  $result['shaS'] = u8ToB64(substr($h, 3, 15));
}

function op_verify () {
  global $result, $args;
  $x = $args['x'];
  $sign = $args['sign'];
  $pubPem = $args['pubPem'];

  $isValid = openssl_verify($x, $sign, $pubPem, OPENSSL_ALGO_SHA256);
  $result['status'] = $isValid;
}

function opGetSafe ($arg) {
  /*
    $x = getSafe(arg['userId']);
    $m = $x['m'];
    $safe = $x['safe'];
    if (!isset($safe)) {
      $reseult['status'] = 1;
      sleep(3);
      return null
    }

    if (arg['shk'] && safe.hhk === Crypt.shaS(arg['shk']))
      return safe

    let ok = false
    const sh1p = arg['sh1p']
    const sh1r = arg['sh1r']
    if (sh1p && safe.hhp1 === Crypt.shaS(sh1p)) ok = true
    else if (sh1r && safe.hhr1 === Crypt.shaS(sh1r)) ok = true
    if (ok) return safe
    
    this.setRes('status', 2)
    await Util.sleep(3000)
    return null
    */
  }

?>