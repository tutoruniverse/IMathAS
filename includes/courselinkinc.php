<?php
// IMathAS: shared helpers for internal course-navigation links inserted via the
// TinyMCE "courselink" plugin -- URL building, itemorder tree lookups, and the
// remap/strip logic used during course copy and export.

// Builds the stable URL for a course item. $itemtype is one of
// Assessment|LinkedText|Forum|Wiki|Drill|InlineText|Block.
// $id is the type table's row id (imas_assessments.id, etc) for leaf types,
// or the block's stored 'id' field for Block.
//
// InlineText has no page of its own -- it's shown wherever it currently sits
// in the folder tree -- so rather than baking in the folder/blockid it
// happened to be in at insert time (which would go stale the moment someone
// moves it), we pass its own stable typeid via showinline=, and course.php
// resolves that to wherever the item lives *at click time* (see
// findLeafParentPath() below), then the #inline anchor scrolls to it.
function getCourseItemUrl($itemtype, $cid, $id) {
    global $DBH, $imasroot;
    $cid = intval($cid);
    $id = intval($id);
    switch ($itemtype) {
        case 'Assessment':
            $stm = $DBH->prepare("SELECT ver FROM imas_assessments WHERE id=:id");
            $stm->execute(array(':id' => $id));
            $ver = $stm->fetchColumn();
            if ($ver > 1) {
                return "$imasroot/assess2/?cid=$cid&aid=$id";
            } else {
                return "$imasroot/assessment/showtest.php?id=$id&cid=$cid";
            }
        case 'LinkedText':
            return "$imasroot/course/showlinkedtext.php?cid=$cid&id=$id";
        case 'Forum':
            return "$imasroot/forums/thread.php?cid=$cid&forum=$id";
        case 'Wiki':
            return "$imasroot/wikis/viewwiki.php?cid=$cid&id=$id";
        case 'Drill':
            return "$imasroot/course/drillassess.php?cid=$cid&daid=$id";
        case 'InlineText':
            return "$imasroot/course/course.php?cid=$cid&showinline=$id#inline$id";
        case 'Block':
            return "$imasroot/course/course.php?cid=$cid&blockid=$id";
        default:
            return false;
    }
}

// Recursively scans an itemorder tree ($items, as unserialized from
// imas_courses.itemorder) for a block whose stored 'id' field matches
// $blockid, returning the reconstructed 'folder' path string (e.g. "0-1-2"),
// or false if not found. Mirrors finditeminblock() in showlinkedtextpublic.php,
// but matches on block id rather than leaf item id.
function findBlockPath($items, $blockid, $parent = '0') {
    if (!is_array($items)) {
        return false;
    }
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $path = $parent . '-' . ($k + 1);
            if (isset($item['id']) && intval($item['id']) === intval($blockid)) {
                return $path;
            }
            if (!empty($item['items'])) {
                $found = findBlockPath($item['items'], $blockid, $path);
                if ($found !== false) {
                    return $found;
                }
            }
        }
    }
    return false;
}

// Recursively scans an itemorder tree for the leaf item (imas_items.id)
// $leafItemId, returning the 'folder' path string of the block that
// currently contains it (e.g. "0" if top-level, "0-1-2" if nested), or false
// if the leaf isn't found anywhere in the tree. Used to resolve
// course.php's showinline= to a folder= at request time, so InlineText
// course links keep working no matter how often the item gets moved.
function findLeafParentPath($items, $leafItemId, $parent = '0') {
    if (!is_array($items)) {
        return false;
    }
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $found = findLeafParentPath($item['items'], $leafItemId, $parent . '-' . ($k + 1));
            if ($found !== false) {
                return $found;
            }
        } else if ($item == $leafItemId) {
            return $parent;
        }
    }
    return false;
}

// Scans $html for <a class="courselink" ...> tags and removes them, keeping
// the inner text. Used on export, where course-navigation links are
// meaningless outside the source install.
function stripCourseLinks($html) {
    if (strpos($html, 'courselink') === false) {
        return $html;
    }
    return preg_replace_callback(
        '/<a\s+[^>]*\bclass="[^"]*\bcourselink\b[^"]*"[^>]*>(.*?)<\/a>/is',
        function ($m) {
            return $m[1];
        },
        $html
    );
}

// imas_assessments.intro is USUALLY plain HTML, but for assessments using
// per-question intro sections it's instead a JSON-encoded array: element 0
// is the general intro HTML, and each element after that is an object with
// its own "text" HTML field (see assessment/showtest.php's
// `json_decode($testsettings['intro'],true)` handling, which is what
// distinguishes the two formats -- plain HTML never happens to be valid
// JSON). A blind regex replace across the whole raw value would silently
// fail to match anything inside the JSON case (its HTML attributes are
// backslash-escaped, e.g. class=\"courselink\", so a literal `"` pattern
// never matches) -- not corrupting, just silently not doing its job. This
// decodes when needed, applies $transform to each actual HTML piece, and
// re-encodes, so callers can pass either stripCourseLinks or a
// remapCourseLinks closure and have it work for both formats.
function transformAssessmentIntro($rawIntro, callable $transform) {
    if ($rawIntro === null || $rawIntro === '') {
        return $rawIntro;
    }
    $decoded = json_decode($rawIntro, true);
    if (!is_array($decoded)) {
        // plain HTML intro
        return $transform($rawIntro);
    }
    $changed = false;
    if (isset($decoded[0]) && is_string($decoded[0])) {
        $new = $transform($decoded[0]);
        if ($new !== $decoded[0]) {
            $decoded[0] = $new;
            $changed = true;
        }
    }
    for ($i = 1, $n = count($decoded); $i < $n; $i++) {
        if (isset($decoded[$i]['text']) && is_string($decoded[$i]['text'])) {
            $new = $transform($decoded[$i]['text']);
            if ($new !== $decoded[$i]['text']) {
                $decoded[$i]['text'] = $new;
                $changed = true;
            }
        }
    }
    return $changed ? json_encode($decoded) : $rawIntro;
}

// Scans $html for <a class="courselink" ...> tags and rewrites them for a
// course copy from $sourcecid to $destcid, using copyiteminc.php's tracking
// maps ($itemtypemap, $blockidmap -- keyed the same way copyiteminc.php
// already keys them). Only called during copy (needs $DBH).
function remapCourseLinks($html, $sourcecid, $destcid, &$itemtypemap, &$blockidmap) {
    global $DBH;
    if (strpos($html, 'courselink') === false) {
        return $html;
    }
    $sourcecid = intval($sourcecid);
    $destcid = intval($destcid);
    $samecourse = ($sourcecid === $destcid);
    return preg_replace_callback(
        '/<a\s+([^>]*\bclass="[^"]*\bcourselink\b[^"]*"[^>]*)>(.*?)<\/a>/is',
        function ($m) use ($sourcecid, $destcid, $samecourse, &$itemtypemap, &$blockidmap, $DBH) {
            $attrs = $m[1];
            $inner = $m[2];

            $type = '';
            $linkcid = 0;
            $id = 0;
            if (preg_match('/data-courselink-type="([^"]*)"/i', $attrs, $tm)) {
                $type = $tm[1];
            }
            if (preg_match('/data-courselink-cid="([^"]*)"/i', $attrs, $cm)) {
                $linkcid = intval($cm[1]);
            }
            if (preg_match('/data-courselink-id="([^"]*)"/i', $attrs, $im)) {
                $id = intval($im[1]);
            }

            if ($type === '' || $linkcid !== $sourcecid) {
                // not a courselink we understand, or points at some other
                // course entirely -- unaffected by this copy
                return $m[0];
            }

            if ($type === 'Block') {
                if (isset($blockidmap[$id])) {
                    $newid = $blockidmap[$id];
                    $href = getCourseItemUrl('Block', $destcid, $newid);
                    return '<a href="' . $href . '" class="courselink" data-courselink-type="Block" data-courselink-cid="' . $destcid . '" data-courselink-id="' . $newid . '">' . $inner . '</a>';
                }
                if ($samecourse) {
                    return $m[0]; // target untouched, still valid where it is
                }
                return $inner;
            }

            $key = $type . $id;
            if (isset($itemtypemap[$key])) {
                $newid = $itemtypemap[$key];
                $href = getCourseItemUrl($type, $destcid, $newid);
                if ($href === false) {
                    return $inner;
                }
                return '<a href="' . $href . '" class="courselink" data-courselink-type="' . $type . '" data-courselink-cid="' . $destcid . '" data-courselink-id="' . $newid . '">' . $inner . '</a>';
            }

            if ($samecourse) {
                return $m[0]; // target untouched, still valid where it is
            }

            if ($type === 'Assessment') {
                // anchor to the *start* of ancestors -- that's always the
                // most recent copy-from entry (copyiteminc.php prepends new
                // ones), so this only matches an assessment that was copied
                // directly from sourcecid's $id, not one where that pair
                // just happens to appear somewhere deeper in its lineage
                $anregex = '^' . $sourcecid . ':' . $id . MYSQL_RIGHT_WRDBND;
                $stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE courseid=:courseid AND ancestors REGEXP :anregex LIMIT 1");
                $stm->execute(array(':courseid' => $destcid, ':anregex' => $anregex));
                $foundid = $stm->fetchColumn();
                if ($foundid !== false) {
                    $href = getCourseItemUrl('Assessment', $destcid, $foundid);
                    return '<a href="' . $href . '" class="courselink" data-courselink-type="Assessment" data-courselink-cid="' . $destcid . '" data-courselink-id="' . $foundid . '">' . $inner . '</a>';
                }
            }

            return $inner;
        },
        $html
    );
}
