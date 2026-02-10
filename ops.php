<?php
function u8ToB64 ($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64ToU8 ($b64) {
  if (!$b64) return null;
  $diff = strlen($b64) % 4;
  $x = $b64;
  if ($diff !== 0) 
    $x = $b64 . substr('====', 0, 4 - $diff);
  $y = str_replace('-', '+', str_replace('_', '/', $x));
  return base64_decode($y);
}

function shaS($bin) {
  $h = hash('sha256', $bin, true);
  return u8ToB64(substr($h, 3, 15));
}

function op_shaS() {
  global $mysqli, $result, $opName, $args;
  $result['shaS'] = shaS(b64ToU8($args['input']));
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
  global $result;
  $x = getSafe($arg['userId']);
  $m = $x['m'];
  $safe = $x['safe'];
  if (!isset($safe)) {
    $result['status'] = 1;
    sleep(3);
    return null;
  }
  $hhk = shaS(b64ToU8($arg['shk']));
  if ($arg['shk'] && $safe['hhk'] === $hhk)
    return $safe;

  $ok = false;
  $sh1p = b64ToU8($arg['sh1p']);
  $sh1r = b64ToU8($arg['sh1r']);
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
  $hhk = shaS(b64ToU8($args['shk']));
  $safe = msgpack_unpack($bin);
  if (isset($safe) && $hhk === $safe['hhk']) {
    $result['status'] = 0;
    $result['safe'] = $safe;
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
  $status = 0;
  $s0 = $args['sh0'];
  $x = getSafe($s0);
  $m = $x['m'];
  $safe = $x['safe'];
  if (!$safe) {
    $status = 3;
    $safe = null;
    $byP = false;
  } else {
    $hh1 = shaS(b64ToU8($args['sh1']));
    if ($m === 1 && $safe['hhp1'] === $hh1) {
      $byP = true;
      $status = 0;
    } else if ($m === 2 && $safe['hhr1'] === $hh1) {
      $byP = false;
      $status = 0;
    } else {
      $status = 3;
      $safe = null;
      $byP = false;
    }
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
  $hhk = shaS(b64ToU8($args['shk']));
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
  if (!isset($safe['devices'])) $safe['devices'] = [];
  $dev = $safe['devices'][$devId];
  if (!isset($dev)) {
    $result['status'] = 2;
    return;
  }

  // vérifie par `Va` que `sign` est bien la signature de 'pincx'
  // la signature est censée être en ASN1
  $sign = b64ToU8($dev['sign']);
  $isValid = openssl_verify(b64ToU8($pincx), $sign, $dev['Va'], OPENSSL_ALGO_SHA256);
  if ($isValid !== 1) {
    $dev['nbe']++;
    if ($dev['nbe'] > 2) {
      unset($safe['devices'][$devId]);
      $result['status'] = 5;
    } else $result['status'] = 4;
    if (count($safe['devices']) === 0) unset($safe['devices']);
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
  if (!isset($safe['devices'])) $safe['devices'] = [];
  $safe['devices'][$td['devId']] = $d;
  updSafe($safe);
  $result['status'] = 0;
  $result['safe'] = $safe;
}

function op_untrustDevices () {
  global $result, $args;
  $td = $args['untrustDev'];
  $safe = opGetSafe($td);
  if (!isset($safe)) return;
  
  if (isset($safe['devices'])) {
    foreach ($td['devIds'] as $id)
      unset($safe['devices'][$id]);
    if (count($safe['devices']) === 0) unset($safe['devices']);
    updSafe($safe);
  }
  $result['status'] = 0;
  $result['safe'] = $safe;
}

function op_setAboutProfile () {
  global $result, $args;
  $ab = $args['aboutProfile'];
  $safe = opGetSafe($ab);
  if (!isset($safe)) return;
  
  if (isset($safe['profiles']) 
    && isset($safe['profiles'][$ab['app']])
    && isset($safe['profiles'][$ab['app']][$ab['profId']])) {
    $prf = msgpack_unpack(b64ToU8($safe['profiles'][$ab['app']][$ab['profId']]));
    $prf['about'] = $ab['about'];
    $prf2 = u8ToB64(msgpack_pack($prf));
    $safe['profiles'][$ab['app']][$ab['profId']] = $prf2;
    updSafe($safe);
  }
  $result['status'] = 0;
  $result['safe'] = $safe;
}

function op_updateCreds () {
  global $result, $args;
  $uc = $args['updateCreds'];
  $safe = opGetSafe($uc);
  if (!isset($safe)) return;
  
  if (!isset($safe['profiles'])) $safe['profiles'] = [];
  if (!isset($safe['creds'])) $safe['creds'] = [];

  if (!isset($safe['profiles'][$uc['app']])) 
    $safe['profiles'][$uc['app']] = [];
  foreach ($uc['profiles'] as $profId => $value) 
    $safe['profiles'][$uc['app']][$profId] = $value;
  foreach ($uc['delprofs'] as $profId) 
    unset($safe['profiles'][$uc['app']][$profId]);
  if (count($safe['profiles'][$uc['app']]) === 0)
    unset($safe['profiles'][$uc['app']]);
  if (count($safe['profiles']) === 0)
    unset($safe['profiles']);

  if (!isset($safe['creds'][$uc['app']]))
    $safe['creds'][$uc['app']] = [];
  foreach ($uc['creds'] as $credId => $value) 
    $safe['creds'][$uc['app']][$credId] = $value;
  foreach ($uc['delcreds'] as $credId) 
    unset($safe['creds'][$uc['app']][$credId]);
  if (count($safe['creds'][$uc['app']]) === 0)
    unset($safe['creds'][$uc['app']]);
  if (count($safe['creds']) === 0)
    unset($safe['creds']);

  updSafe($safe);
  $result['status'] = 0;
  if (!isset($uc['nosafe'])) $result['safe'] = $safe;
}

function op_updatePrefs () {
  global $result, $args;
  $up = $args['updatePrefs'];
  $safe = opGetSafe($up);
  if (!isset($safe)) return;
  
  if (!isset($safe['prefs'])) $safe['prefs'] = [];

  if (!isset($safe['prefs'][$up['app']])) 
    $safe['prefs'][$up['app']] = [];
  foreach ($up['prefs'] as $code => $value) 
    $safe['prefs'][$up['app']][$code] = $value;
  foreach ($up['delprefs'] as $code) 
    unset($safe['prefs'][$up['app']][$code]);
  if (count($safe['prefs'][$up['app']]) === 0)
    unset($safe['prefs'][$up['app']]);
  if (count($safe['prefs']) === 0)
    unset($safe['prefs']);

  updSafe($safe);
  $result['status'] = 0;
  if (!isset($uc['nosafe'])) $result['safe'] = $safe;
}

function op_transmitCred () {
  global $result, $args;
  $tc = $args['transmitCred'];
  $x = getSafe($tc['targetId']);
  $m = $x['m'];
  $safe = $x['safe'];

  if (!isset($safe)) {
    $result['status'] = 2;
    return;
  }

  if (!isset($safe['creds'])) $safe['creds'] = [];
  if (!isset($safe['creds'][$tc['app']])) 
    $safe['creds'][$tc['app']] = [];
  $ix = '$' . $tc['credId'];
  $safe['creds'][$tc['app']][$ix] = $tc['crpub'];
  if (count($safe['creds'][$tc['app']]) === 0)
    unset($safe['creds'][$tc['app']]);
  if (count($safe['creds']) === 0)
    unset($safe['creds']);

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
  $x = getSafe($id);
  $m = $x['m'];
  $safe = $x['safe'];
  $result['crypt'] = isset($safe) ? $safe['C'] : null; 
  $result['verify'] = isset($safe) ? $safe['V'] : null; 
}

function op_delSafe () {
  global $result, $args;
  $userId = $args['userId'];
  $safe = opGetSafe($args);
  if (!isset($safe)) return;
  delSafe($userId);
  $result['status'] = 0;
}

?>