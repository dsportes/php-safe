<?php

function op_echo() {
  global $mysqli, $result, $opName, $args;
  $result['echo'] = $args;
}

function u8ToB64($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function shaS($bin) {
  $h = hash('sha256', $bin, true);
  return u8ToB64(substr($h, 3, 15));
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
  $x = getSafe($arg['userId']);
  $m = $x['m'];
  $safe = $x['safe'];
  if (!isset($safe)) {
    $reseult['status'] = 1;
    sleep(3);
    return null;
  }

  if (arg['shk'] && $safe['hhk'] === shaS(arg['shk']))
    return $safe;

  $ok = false;
  $sh1p = $arg['sh1p'];
  $sh1r = $arg['sh1r'];
  if ($sh1p && $safe['hhp1'] === shaS($sh1p)) $ok = true;
  else if ($sh1r && $safe['hhr1'] === shaS($sh1r)) $ok = true;
  if ($ok) return $safe;
  
  $result['status'] = 2;
  sleep(3);
  return null;
}

function op_createSafe () {
  global $result, $args;
  $safe = $args['safe'];
  $ret = newSafe($safe);
  if ($ret !== 0) sleep(3);
  $result['status'] = $ret;
}

function op_restoreSafe () {
  global $result, $args;
  $safe = $args['safe'];
  $ret = restoreSafe($safe);
  if ($ret !== 0) sleep(3);
  $result['status'] = $ret;
}

function op_getBinSafe () {
  global $result, $args;
  $x = getBinSafe($args['userId']);
  $m = $x[0];
  $bin = $x[1];
  $hhk = shaS($args['shk']);
  $safe = msgpack_unpack($bin);
  if (isset($safe) && $hhk === $safe['hhk']) {
    $result['status'] = 0;
    $result['binsafe'] = $bin;
  } else {
    $result['status'] = 1;
    sleep(3);
  }
}

function op_updCodesSafe () {
  global $result, $args;
  $safeNew = $args['safeCodes'];
  $x = getSafe($safeNew['id']);
  $m = $x['m'];
  $safe = $x['safe'];
  if (!isset($safe)) {
    $reseult['status'] = 1;
    sleep(3);
    return;
  }
  $safe['pseudo'] = $safeNew['pseudo'];
  $safe['hp0'] = $safeNew['hp0'];
  $safe['hr0'] = $safeNew['hr0'];
  $safe['hhp1'] = $safeNew['hhp1'];
  $safe['hhr1'] = $safeNew['hhr1'];
  $safe['Ka'] = $safeNew['Ka'];
  $safe['Kr'] = $safeNew['Kr'];
  $ret = updPRSafe($safe);
  $result['status'] = $ret;
  if ($ret !== 0) sleep(3);
  else $result['safe'] = $safe;
}

function op_openSafeByPR () {
  global $result, $args;
  $byP = false;
  $status = 1;
  $s0 = u8ToB64($args['sh0'], true);
  $x = getSafe($s0);
  $m = $x['m'];
  $safe = $x['safe'];
  $hhp1 = shaS($args['sh1']);
  if ($safe && $safe['hhp1'] === $hhp1) {
    $byP = $m === 1;
    $status = 0;
  }
  $result['status'] = $status;
  $result['safe'] = $safe;
  $result['byP'] = $byP;
  if ($status !== 0) sleep(3);
}

function op_openSafeById () {
  global $result, $args;
  $x = getSafe($args['userId']);
  $m = $x['m'];
  $safe = $x['safe'];
  $hhk = shaS($args['shk']);
  if ($safe && $hhk === $safe['hhk']) {
    $result['status'] = 0;
    $result['safe'] = $safe;
  } else {
    $result['status'] = 1;
    if ($status !== 0) sleep(3);
  }
}

function op_openSafeByPin () {
  global $result, $args;
  $userId = $args['userId'];
  $devId = $args['devId'];
  $pincx = $args['pincx'];

  $x = getSafe($args['userId']);
  $m = $x['m'];
  $safe = $x['safe'];
  if (!isset($safe)) {
    $result['status'] = 2;
    return;
  }
  $dev = $safe['devices'][$devId];
  if (!isset($dev)) {
    $result['status'] = 2;
    return;
  }

  // vérifie par `Va` que `sign` est bien la signature de 'pincx'
  // la signature est censée être en ASN1
  $isValid = openssl_verify($pincx, $dev['sign'], $dev['Va'], OPENSSL_ALGO_SHA256);
  if ($isValid !== 1) {
    $dev['nbe']++;
    if ($dev['nbe'] > 2) {
      unset($safe['devices'][$devId]);
      $result['status'] = 5;
    } else $result['status'] = 4;
    updSafe($safe);
    return;  
  }
  if ($dev['nbe'] !== 0) {
    $dev['nbe'] = 0;
    updSafe($safe);
  }
  $result['status'] = 0;
  $result['cy'] = $dev['cy'];
}

function op_trustDevice () {
  global $result, $args;
  $td = $args['trustDev'];
  $safe = opGetSafe($td);
  if (!isset($safe)) return;

  $d = [
    'devName' => $td['devName'],
    'Va' => $td['Va'],
    'cy' => $td['cy'],
    'sign' => $td['sign'],
    'nbe' => 0
  ];
  $safe['devices'][$td['devId']] = $d;
  updSafe($safe);
  $result['status'] = 0;
  $result['safe'] = $safe;
}

function op_untrustDevice () {
  global $result, $args;
  $td = $args['trustDev'];
  $safe = opGetSafe($td);
  if (!isset($safe)) return;
  
  foreach ($td['devIds'] as $id) unset($safe['devices'][$id]);

  updSafe($safe);
  $result['status'] = 0;
  $result['safe'] = $safe;
}

function op_setAboutProfile () {
  global $result, $args;
  $ab = $args['aboutProfile'];
  $safe = opGetSafe($ab);
  if (!isset($safe)) return;
  
  $appe = $safe['profiles'][$ab['app']];
  if (!isset($appe)) { 
    $appe = []; 
    $safe['profiles'][$ab['app']] = $appe;
  }

  $prf = $appe[$ab['profId']];
  if (!isset($prf)) { 
    $prf = [ 'creds' => [] ]; 
    $appe[$ab['profId']] = $prf ;
  }
  $prf['about'] = $ab['about'];
  updSafe($safe);
  $result['status'] = 0;
  $result['safe'] = $safe;
}

function op_updateCreds () {
  global $result, $args;
  $tc = $args['updateCreds'];
  $safe = opGetSafe($uc);
  if (!isset($safe)) return;
  
  $appp = $safe['profiles'][$uc['app']];
  if (!isset($appp)) { 
    $appp = []; 
    $safe['profiles'][$uc['app']] = $appp;
  }
  foreach ($uc['profiles'] as $profId) 
    $appp[$profId] = $uc['profiles'][$profId];
  foreach ($uc['delprofs'] as $profId) 
    unset($uc['profiles'][$profId]);

  updSafe($safe);
  $result['status'] = 0;
  if (!isset($uc['nosafe'])) $result['safe'] = $safe;
}

function op_transmitCred () {
  global $result, $args;
  $tc = $args['transmitCred'];
  $x = getSafe($args['userId']);
  $m = $x['m'];
  $safe = $x['safe'];

  if (!isset($safe)) {
    $result['status'] = 2;
    return;
  }

  $appc = $safe['creds'][$tc['app']];
  if (!isset($appc)) { 
    $appc = []; 
    $safe['creds'][$tc['app']] = $appc;
  }
  $appc['$' + $tc['credId']] = msgpack_pack([$tc['cryptedCred'], $tc['pubC']]);
  updSafe($safe);
  $result['status'] = 0;
}

function op_statusSafe () {
  global $result, $args;
  $id = $args['id'];
  $hp0 = $args['hp0'];
  $hr0 = $args['hr0'];
  $ret = statusSafe($id, $hp0, $hr0);
  $result['statusSafe'] = $ret;
}

function op_getPublicKeys () {
  global $result, $args;
  $id = $args['id'];
  $x = getSafe($args['userId']);
  $m = $x['m'];
  $safe = $x['safe'];
  $result['crypt'] = isset($safe) ? $safe['C'] : null; 
  $result['verify'] = isset($safe) ? $safe['V'] : null; 
}
?>