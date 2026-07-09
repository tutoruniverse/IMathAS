<?php

//Add created_on field to a bunch of tables
$DBH->beginTransaction();

$tables = array('imas_users','imas_groups','imas_courses','imas_ltiusers',
	'imas_lti_courses','imas_assessments','imas_students');

foreach ($tables as $table) {
	// check to see if created_on already exists

	$stm = $DBH->prepare(
		"SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
         FROM information_schema.columns 
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? 
           AND COLUMN_NAME = 'created_on'"
    );
	$stm->execute([$GLOBALS['dbname'], $table]);
	$existing = $stm->fetch();
	if ($existing !== false) {
		// column exists; skip
		continue;
	}

	// add column
	$query = "ALTER TABLE `$table` ADD COLUMN `created_on` TIMESTAMP NULL DEFAULT NULL";
	$res = $DBH->query($query);
	if ($res===false) {
		echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
		$DBH->rollBack();
		return false;
	}

	// change default to be current when row added
	$query = "ALTER TABLE `$table` MODIFY COLUMN `created_on` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
	$res = $DBH->query($query);
	if ($res===false) {
		echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
		$DBH->rollBack();
		return false;
	}
}
 
$DBH->commit();

echo "<p style='color: green;'>✓ Added created_on columns</p>";

return true;