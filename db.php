<?php

function currentMonth () {
  return intval((new DateTimeImmutable())->format('Ym'));
}

function test1 ($mysqli, $data) {
  global $mysqli;
  $stmt = $mysqli->prepare("INSERT INTO safe(id, hp0, hr0, lam, data) VALUES (?, ?, ?, ?, ?)");
  $id = 'toto';
  $hp0 = 'titi';
  $hr0 = 'tutu';
  $lam = 12;
  $stmt->bind_param('sssib', $id, $hp0, $hr0, $lam, $null);
  $stmt->send_long_data(0, $data);
  $stmt->execute();
}

function chunks ($stmt, $data, $idx) {
  $chl = 200000;
  $ln = strlen($data);
  $pos = 0;
  while ($pos <= $ln) {
    $l = $ln - $pos;
    $chunk = substr($data, $pos, $l < $chl ? $l : $chl);
    $stmt->send_long_data($idx, $chunk);
    $pos += $chl;
  }
}

/*
function test2 ($bin) {
  global $mysqli;
  $mysqli->autocommit(FALSE);
  $stmt = $mysqli->prepare("UPDATE safe  SET data = ?, lam = ? WHERE id = ?");
  $id = 'toto';
  $lam = time();
  $stmt->bind_param('bis', $null, $lam, $id);
  chunks($stmt, $bin, 0);
  $stmt->execute();
  $mysqli->commit();
}

function test3 () {
  global $mysqli;
  $stmt = $mysqli->prepare("SELECT data FROM safe WHERE id = ?");
  $id = 'toto';
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row['data'];
}
*/

/* Retourne l'objet safe depuis soit son id, soit son p0, soit son r0
null si non trouvé
*/
function getBinSafe ($id) {
  global $mysqli;
  $m = 0;
  $stmt = $mysqli->prepare('SELECT id, lam, data FROM SAFE WHERE id = ?');
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  if (!isset($row)) {
    $m = 1;
    $stmt = $mysqli->prepare('SELECT id, lam, data FROM SAFE WHERE hp0 = ?');
    $stmt->bind_param('s', $id); 
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if (!isset($row)) {
      $stmt = $mysqli->prepare('SELECT id, lam, data FROM SAFE WHERE hr0 = ?');
      $stmt->bind_param('s', $id); 
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      if (!isset($row)) {
        return [m, null];
      }
    }
  }
  $cm = currentMonth();
  if ($row['lam'] !== $cm) {
    $stmt = $mysqli->prepare('UPDATE SAFE SET lam = ? WHERE id = ?');
    $stmt->bind_param('is', $cm, $id); 
    $stmt->execute();
  }
  return [$m, $row['data']];
}

function getSafe ($id) {
  $ret = getBinSafe($id);
  return [ 'm' => $ret[0], 'safe' => ($ret[1] ? msgpack_unpack($ret[1]) : null)];
}

/* Status de création d'un safe - Permet de savoir dans quelles conditions le safe pourrait être "recréé".
- id, hp0, hr0 : id et accès externe 
Retour : { lm, xp, xr }
- lm : last modifidication time du safe d' id donnée. -1 si ce safe n'existe pas.
- xp : true si aucun safe n'a hp0 comme cl& externe OU si le safe d'id existe et a 
hp0 comme clé p0
- xr : idem pour hr0
*/
function statusSafe ($id, $hp0, $hr0) {
  global $mysqli;
  $r = [ 'lm' => -1, 'xp' => true, 'xr' => true ];
  $stmt = $mysqli->prepare('SELECT id, data FROM SAFE WHERE id = ?');
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  if (isset($result)) {
    $row = $result->fetch_assoc();
    $data = msgpack_unpack($row['data']);
    $r['lm'] = isset($data['lm']) ? $data['lm'] : 0;
    if ($data['hp0'] === $hp0) $r['xp'] = true;
    else {
      $stmt2 = $mysqli->prepare('SELECT id FROM SAFE WHERE hp0 = ?');
      $stmt2->bind_param('s', $hp0); 
      $stmt2->execute();
      $result2 = $stmt2->get_result();
      $r['xp'] = !isset($result2);
    }
    if ($data['hr0'] === $hr0) $r['xr'] = true;
    else {
      $stmt3 = $mysqli->prepare('SELECT id FROM SAFE WHERE hr0 = ?');
      $stmt3->bind_param('s', $hr0); 
      $stmt3->execute();
      $result3 = $stmt3->get_result();
      $r['xr'] = !isset($result3);
    }
  } else {
    $r['lm'] = -1;
    $stmt2 = $mysqli->prepare('SELECT id FROM SAFE WHERE hp0 = ?');
    $stmt2->bind_param('s', $hp0); 
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $r['xp'] = !isset($result2);
    $stmt3 = $mysqli->prepare('SELECT id FROM SAFE WHERE hr0 = ?');
    $stmt3->bind_param('s', $hr0); 
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    $r['xr'] = !isset($result3);
  }
  return $r;
}

function newSafe ($safe) {
  global $mysqli;
  $id = $safe['id'];
  $hp0 = $safe['hp0'];
  $hr0 = $safe['hr0'];
  $safe['lm'] = time();
  $lam = currentMonth();
  $stmt = $mysqli->prepare('SELECT id, hp0, hr0 FROM SAFE WHERE id = ?');
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row0 = isset($result) ? $result->fetch_assoc() : null;
  if ((isset($row0) && $row0['hp0'] !== $hp0) || ! isset($row0)) {
    $stmt = $mysqli->prepare('SELECT id, hp0, hr0 FROM SAFE WHERE hp0 = ?');
    $stmt->bind_param('s', $hp0); 
    $stmt->execute();
    $result = $stmt->get_result();
    $row1 = isset($result) ? $result->fetch_assoc() : null;
    if (isset($row1)) return 1;
  }
  if ((isset($row0) && $row0['hr0'] !== $hr0) || ! isset($row0)) {
    $stmt = $mysqli->prepare('SELECT id, hp0, hr0 FROM SAFE WHERE hr0 = ?');
    $stmt->bind_param('s', $hr0); 
    $stmt->execute();
    $result = $stmt->get_result();
    $row2 = isset($result) ? $result->fetch_assoc() : null;
    if (isset($row2)) return 2;
  }
  $data = msgpack_pack($safe);
  if (!isset($row0)) {
    $stmt = $mysqli->prepare('INSERT INTO SAFE (id, hp0, hr0, lam, data) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssib', $id, $hp0, $hr0, $lam, $null);
    chunks($stmt, $data, 4);
    $stmt->execute();
  } else {
    $stmt = $mysqli->prepare('UPDATE SAFE SET hp0 = @hp0, hr0 = @hr0, lam = @lam, data = @data WHERE id = @id');
    $stmt->bind_param('ssibs', $hp0, $hr0, $lam, $null, $id);
    chunks($stmt, $data, 3);
    $stmt->execute();
  }
  return 0;
}

function restoreSafe ($safe) {
  global $mysqli;
  $id = $safe['id'];
  $hp0 = $safe['hp0'];
  $hr0 = $safe('hr0');
  $safe['lm'] = time();
  $lam = currentMonth();
  $stmt = $mysqli->prepare('SELECT id, hp0, hr0 FROM SAFE WHERE id = ?');
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row0 = isset($result) ? $result->fetch_assoc() : null;
  $stmt = $mysqli->prepare('SELECT id, hp0, hr0 FROM SAFE WHERE hp0 = ?');
  $stmt->bind_param('s', $hp0); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row1 = isset($result) ? $result->fetch_assoc() : null;
  if (isset($row1) && $row1['id'] !== $id) return 1;
  $stmt = $mysqli->prepare('SELECT id, hp0, hr0 FROM SAFE WHERE hr0 = ?');
  $stmt->bind_param('s', $hr0); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row2 = isset($result) ? $result->fetch_assoc() : null;
  if (isset($row2) && $row2['id'] !== $id) return 2;
  $data = msgpack_pack($safe);
  if (isset($row0)) {
    $stmt = $mysqli->prepare('INSERT INTO SAFE (id, hp0, hr0, lam, data) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssib', $id, $hp0, $hr0, $lam, $null);
    chunks($stmt, $data, 4);
    $stmt->execute();
  } else {
    $stmt = $mysqli->prepare('UPDATE SAFE SET hp0 = ?, hr0 = ?, lam = ?, data = ? WHERE id = ?');
    $stmt->bind_param('ssibs', $hp0, $hr0, $lam, $null, $id);
    chunks($stmt, $data, 3);
    $stmt->execute();
  }
  return 0;
}

function updPRSafe ($safe) {
  global $mysqli;
  $id = $safe['id'];
  $hp0 = $safe['hp0'];
  $hr0 = $safe('hr0');
  $safe['lm'] = time();
  $lam = currentMonth();
  $stmt = $mysqli->prepare('SELECT id FROM SAFE WHERE hp0 = ?');
  $stmt->bind_param('s', $hp0); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = isset($result) ? $result->fetch_assoc() : null;
  if (isset($row) && $row['id'] !== $id) return 2;
  $stmt = $mysqli->prepare('SELECT id FROM SAFE WHERE hr0 = ?');
  $stmt->bind_param('s', $hr0); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = isset($result) ? $result->fetch_assoc() : null;
  if (isset($row) && $row['id'] !== $id) return 3;
  $data = msgpack_pack($safe);
  $stmt = $mysqli->prepare('UPDATE SAFE SET hp0 = ?, hr0 = ?, lam = ?, data = ? WHERE id = ?');
  $stmt->bind_param('ssibs', $hp0, $hr0, $lam, $null, $id);
  chunks($stmt, $data, 3);
  $stmt->execute();
  return 0;
}

function updSafe ($safe) {
  global $mysqli;
  $id = $safe['id'];
  $safe['lm'] = time();
  $lam = currentMonth();
  $data = msgpack_pack($safe);
  $stmt = $mysqli->prepare('UPDATE SAFE SET hp0 = ?, hr0 = ?, lam = ?, data = ? WHERE id = ?');
  $stmt->bind_param('ssibs', $hp0, $hr0, $lam, $null, $id);
  chunks($stmt, $data, 3);
  $stmt->execute();
}

function delSafe ($id) {
  global $mysqli;
  $stmt = $mysqli->prepare('DELETE FROM SAFE WHERE id = ?');
  $stmt->bind_param('s', $id);
  $stmt->execute();
}

function purgeSafes ($lam) {
  global $mysqli;
  $stmt = $mysqli->prepare('DELETE FROM SAFE WHERE lam < ?');
  $stmt->bind_param('s', $lam);
  $stmt->execute();
}
?>