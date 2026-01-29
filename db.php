<?php

function currentMonth () {
  return intval((new DateTimeImmutable())->format('Ym'));
}

function test1 ($mysqli, $data) {
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

function test2 ($mysqli, $bin) {
  $mysqli->autocommit(FALSE);
  $stmt = $mysqli->prepare("UPDATE safe  SET data = ?, lam = ? WHERE id = ?");
  $id = 'toto';
  $lam = time();
  $stmt->bind_param('bis', $null, $lam, $id);
  chunks($stmt, $bin, 0);
  $stmt->execute();
  $mysqli->commit();
}

function test3 ($mysqli) {
  $stmt = $mysqli->prepare("SELECT data FROM safe WHERE id = ?");
  $id = 'toto';
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  return $row['data'];
}

/* Retourne l'objet safe depuis soit son id, soit son p0, soit son r0
null si non trouvé
*/
function getSafe ($id, $idp0r0) {
  $idx = !isset($idp0r0) || ($idp0r0 === 0) ? 'id' : ($idp0r0 === 1 ? 'hp0' : 'hr0');
  $stmt = $mysqli->prepare('SELECT id, lam, data FROM SAFE WHERE ' . $idx . ' = ?');
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  if (!isset($row)) return null;
  $cm = currentMonth();
  if ($row['lam'] !== $cm) {
    $stmt = $mysqli->prepare('UPDATE SAFE SET lam = ? WHERE id = ?');
    $stmt->bind_param('is', $cm, $id); 
    $stmt->execute();
  }
  return msgpack_unpack($row->data);
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
  $r = [ 'lm' => -1, 'xp' => true, 'xr' => true ];
  $stmt = $mysqli->prepare('SELECT id, data FROM SAFE WHERE id = ?');
  $stmt->bind_param('s', $id); 
  $stmt->execute();
  $result = $stmt->get_result();
  if (isset($result)) {
    $row = $result->fetch_assoc();
    $data = $row['data'];
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
  return r;
}

?>