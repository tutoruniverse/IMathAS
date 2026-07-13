<?php
//Shared helpers for the forum thread-list views (thread.php, newthreads.php,
//flaggedthreads.php) and for posts.php's client-side thread-list cache
//(forumthreadcache.js, sessionStorage): fetching a single page of thread ids
//without pulling the full display data, and rendering the shared
//pagination control.

//JOIN/WHERE fragment (and bound params) for a single forum's thread list.
//filterMode is 'new' or 'flagged' (thread.php's type=new/flagged);
//anything else means no view/tagged filtering (the default listing).
//$dofilter/$limthreads carry thread.php's group+tag filtering (already
//resolved to a literal thread-id list by the caller). This is the single
//source of truth for "which threads match this view" - both thread.php's
//own full-column query and forumThreadIdsForPage() below build on it, so
//a filter/visibility change only needs to happen once.
function forumThreadWhereClause($forumid, $filterMode, $dofilter, $limthreads, $canviewall, $now, $userid) {
	$qarr = [':forumid'=>$forumid, ':now'=>$canviewall?2000000000:$now];
	$joinfrag = '';
	if ($filterMode == 'new' || $filterMode == 'flagged') {
		$joinfrag = 'LEFT JOIN imas_forum_views ON imas_forum_views.threadid=imas_forum_threads.id AND imas_forum_views.userid=:userid ';
		$qarr[':userid'] = $userid;
	}
	$wherefrag = "WHERE imas_forum_threads.forumid=:forumid AND imas_forum_threads.lastposttime<:now ";
	if ($dofilter) {
		$wherefrag .= "AND imas_forum_threads.id IN ($limthreads) ";
	}
	if ($filterMode == 'new') {
		$wherefrag .= "AND (imas_forum_views.lastview IS NULL OR imas_forum_views.lastview < imas_forum_threads.lastposttime) ";
	} else if ($filterMode == 'flagged') {
		$wherefrag .= "AND imas_forum_views.tagged=1 ";
	}
	return [$joinfrag, $wherefrag, $qarr];
}

//Fetches a single page of forum thread ids in the same order
//thread.php's main listing query would produce, without pulling the
//full display data (user names, subjects, etc). Used by posts.php to
//resolve a thread when the client-side thread-list cache
//(forumthreadcache.js, sessionStorage) needs to expand past a page
//boundary for Prev/Next navigation.
function forumThreadIdsForPage($DBH, $forumid, $pageNum, $threadsperpage, $sortby, $dofilter, $limthreads, $canviewall, $now, $userid, $filterMode = 'none') {
	list($joinfrag, $wherefrag, $qarr) = forumThreadWhereClause($forumid, $filterMode, $dofilter, $limthreads, $canviewall, $now, $userid);
	$query = "SELECT ifp.threadid FROM imas_forum_threads JOIN imas_forum_posts AS ifp ON ifp.threadid=imas_forum_threads.id AND ifp.parent=0 ";
	$query .= $joinfrag;
	$query .= $wherefrag;
	if ($sortby==0) {
		$query .= "ORDER BY ifp.posttype DESC,ifp.postdate DESC ";
	} else {
		$query .= "ORDER BY ifp.posttype DESC,imas_forum_threads.lastposttime DESC ";
	}
	$offset = max(0, $pageNum-1) * intval($threadsperpage);
	$query .= "LIMIT $offset," . intval($threadsperpage);
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	$ids = [];
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$ids[] = intval($row[0]);
	}
	return $ids;
}

//FROM/JOIN/WHERE fragment (and bound params) for a course-wide thread
//list. Mirrors newthreads.php's/flaggedthreads.php's visibility rules
//(note: checks $isteacher specifically, not "canviewall" - tutors get the
//same unrestricted view as teachers on these course-wide lists).
//filterMode is 'new' or 'flagged'. Single source of truth shared by
//newthreads.php/flaggedthreads.php's own queries and
//courseThreadIdsForPage() below.
function courseThreadWhereClause($cid, $filterMode, $isteacher, $userid, $now) {
	$qarr = [':now'=>$now, ':courseid'=>$cid, ':userid'=>$userid];
	$fromwhere = "FROM imas_forum_threads ";
	$fromwhere .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forum_threads.lastposttime<:now ";
	if (!$isteacher) {
		$fromwhere .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
	}
	$fromwhere .= "LEFT JOIN imas_forum_views AS mfv ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid ";
	$fromwhere .= "WHERE imas_forums.courseid=:courseid ";
	if (!$isteacher) {
		$fromwhere .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid2)) ";
		$qarr[':userid2'] = $userid;
	}
	if ($filterMode == 'new') {
		$fromwhere .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL)) ";
	} else if ($filterMode == 'flagged') {
		$fromwhere .= "AND mfv.tagged=1 ";
	}
	return [$fromwhere, $qarr];
}

//Course-wide equivalent of forumThreadIdsForPage(). Returns
//[threadid, forumid] pairs, since a course-wide list spans forums. Used by
//posts.php's edge-resolution for type=coursenew/courseflagged.
function courseThreadIdsForPage($DBH, $cid, $pageNum, $threadsperpage, $filterMode, $isteacher, $userid, $now) {
	list($fromwhere, $qarr) = courseThreadWhereClause($cid, $filterMode, $isteacher, $userid, $now);
	$query = "SELECT imas_forum_threads.id, imas_forums.id " . $fromwhere;
	$query .= "ORDER BY imas_forum_threads.lastposttime DESC ";
	$offset = max(0, $pageNum-1) * intval($threadsperpage);
	$query .= "LIMIT $offset," . intval($threadsperpage);
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	$ids = [];
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$ids[] = [intval($row[0]), intval($row[1])];
	}
	return $ids;
}

//Shared "Page: 1 2 3 ... Previous/Next" control used by thread.php,
//newthreads.php, and flaggedthreads.php. $baseUrl is everything before
//"&page=N" (cid/forum/type/grp/etc already included). Returns '' when
//there's nothing to page through.
function renderThreadListPager($page, $numpages, $baseUrl) {
	if ($numpages <= 1) {
		return '';
	}
	$prevnext = "Page: ";
	if ($page < $numpages/2) {
		$min = max(2,$page-4);
		$max = min($numpages-1,$page+8+$min-$page);
	} else {
		$max = min($numpages-1,$page+4);
		$min = max(2,$page-8+$max-$page);
	}
	if ($page==1) {
		$prevnext .= "<b>1</b> ";
	} else {
		$prevnext .= "<a href=\"$baseUrl&page=1\">1</a> ";
	}
	if ($min!=2) { $prevnext .= " ... ";}
	for ($i = $min; $i<=$max; $i++) {
		if ($page == $i) {
			$prevnext .= "<b>$i</b> ";
		} else {
			$prevnext .= "<a href=\"$baseUrl&page=$i\">$i</a> ";
		}
	}
	if ($max!=$numpages-1) { $prevnext .= " ... ";}
	if ($page == $numpages) {
		$prevnext .= "<b>$numpages</b> ";
	} else {
		$prevnext .= "<a href=\"$baseUrl&page=$numpages\">$numpages</a> ";
	}
	$prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

	if ($page>1) {
		$prevnext .= "<a href=\"$baseUrl&page=".($page-1)."\">Previous</a> ";
	} else {
		$prevnext .= "Previous ";
	}
	if ($page < $numpages) {
		$prevnext .= "| <a href=\"$baseUrl&page=".($page+1)."\">Next</a> ";
	} else {
		$prevnext .= "| Next ";
	}
	return $prevnext;
}
