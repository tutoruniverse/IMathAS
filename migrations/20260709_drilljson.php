<?php

$DBH->beginTransaction();

$query = 'ALTER TABLE imas_assessments ADD COLUMN `drilljson` TEXT NOT NULL DEFAULT ""';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add drilljson</p>';

return true;
