<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_passkeys` (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  credential_id VARCHAR(512) NOT NULL UNIQUE,
  public_key    TEXT NOT NULL,
  sign_count    INT DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET UTF8 COLLATE utf8_general_ci ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';

$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add table imas_passkeys</p>';

return true;
