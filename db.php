<?php

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

?>