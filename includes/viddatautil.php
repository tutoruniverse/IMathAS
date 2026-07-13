<?php
//Functions for maintaining serialized video cueing data (viddata) on
//imas_assessments when the itemorder changes (add, reorder, insert, remove, duplicate).
//(c) IMathAS 2026

//Expand an itemorder string into a flat array of qid, keyed by the
//sequential question-slot number (matching how addvideotimes.php numbers questions).
function vidDataQidsByNum($itemOrder) {
	$qidbynum = array();
	if (trim($itemOrder) == '') {
		return $qidbynum;
	}
	$qorder = explode(',', $itemOrder);
	$k = 0;
	for ($i=0;$i<count($qorder);$i++) {
		if (strpos($qorder[$i],'~')!==false) {
			$qids = explode('~',$qorder[$i]);
			if (strpos($qids[0],'|')!==false) { //pop off nCr
				$choose = explode('|', $qids[0]);
				for ($j=0;$j<$choose[0];$j++) { // add the number from pool we're using
					$qidbynum[$k] = $qids[1+$j];
					$k++;
				}
			} else {
				$qidbynum[$k] = $qids[0];
				$k++;
			}
		} else {
			$qidbynum[$k] = $qorder[$i];
			$k++;
		}
	}
	return $qidbynum;
}

//Pop the trailing "final segment" entry (title only, no endtime) off an
//unserialized viddata array, if present, so it doesn't get stranded
//mid-array by callers that append/insert entries.
function vidDataPopFinalSeg(&$viddata) {
	if (count($viddata) > 0 && !isset($viddata[count($viddata)-1][1])) {
		return array_pop($viddata);
	}
	return null;
}

//Remap a serialized viddata's question-number references from $oldItemOrder
//to $newItemOrder. Handles reordering, insertion, and removal of questions.
function remapVidData($oldItemOrder, $newItemOrder, $viddata) {
	if ($viddata == '') {
		return $viddata;
	}
	$viddata = unserialize($viddata);
	$finalseg = vidDataPopFinalSeg($viddata);

	$qidbynum = vidDataQidsByNum($oldItemOrder);
	$newbynum = vidDataQidsByNum($newItemOrder);
	$qidbynumflip = array_flip($qidbynum);

	$newviddata = array();
	$newviddata[0] = $viddata[0];
	for ($i=0;$i<count($newbynum);$i++) {   //for each new item
		if (!isset($qidbynumflip[$newbynum[$i]])) {
			// question wasn't in the old order (newly added/inserted)
			$newviddata[] = array('','',$i);
			continue;
		}
		$oldnum = $qidbynumflip[$newbynum[$i]];
		$found = false; //look for old item in viddata
		for ($j=1;$j<count($viddata);$j++) {
			if (isset($viddata[$j][2]) && $viddata[$j][2]==$oldnum) {
				//if found, copy data, and any non-question data following
				$new = $viddata[$j];
				$new[2] = $i;  //update question number;
				$newviddata[] = $new;
				$j++;
				while (isset($viddata[$j]) && !isset($viddata[$j][2])) {
					$newviddata[] = $viddata[$j];
					$j++;
				}
				$found = true;
				break;
			}
		}
		if (!$found) {
			//item was not found in viddata.  it should have been.
			//can happen if the first item in a group was removed, perhaps
			//Add a blank item
			$newviddata[] = array('','',$i);
		}
	}
	//any old items not matched to a new position will not get copied.
	if ($finalseg !== null) {
		$newviddata[] = $finalseg;
	}
	return serialize($newviddata);
}

//Append blank placeholder video segments for $numNew questions newly
//appended to the end of $oldItemOrder (before they were added).
function appendBlankVidSegments($oldItemOrder, $numNew, $viddata) {
	if ($viddata == '' || $numNew <= 0) {
		return $viddata;
	}
	$nextnum = count(vidDataQidsByNum($oldItemOrder));
	$viddata = unserialize($viddata);
	$finalseg = vidDataPopFinalSeg($viddata);
	for ($i=$nextnum;$i<$nextnum+$numNew;$i++) {
		$viddata[] = array('','',$i);
	}
	if ($finalseg !== null) {
		$viddata[] = $finalseg;
	}
	return serialize($viddata);
}
