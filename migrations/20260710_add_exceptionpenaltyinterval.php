<?php

//change
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_assessments` ADD COLUMN `exceptionpenaltyinterval` SMALLINT UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE `imas_exceptions` ADD COLUMN `exceptionpenaltyinterval` SMALLINT UNSIGNED NULL DEFAULT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE `imas_exceptions` ADD COLUMN `exceptionpenaltyscope` ENUM('both','exception_only') NOT NULL DEFAULT 'both'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "ALTER TABLE `imas_exceptions` ADD COLUMN `manualexceptionend` INT UNSIGNED NULL DEFAULT NULL";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ add exceptionpenaltyinterval and penaltyscope columns</p>";

return true;
