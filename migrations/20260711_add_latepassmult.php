<?php

$DBH->beginTransaction();

$query = 'ALTER TABLE imas_students ADD COLUMN `latepassmult` DECIMAL(3,2) UNSIGNED NOT NULL DEFAULT \'1.0\'';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add latepassmult to imas_students</p>';

return true;
