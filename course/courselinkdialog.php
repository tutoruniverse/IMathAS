<?php
// IMathAS: Course Link picker dialog, opened by the TinyMCE "courselink"
// plugin (tinymce8/plugins/courselink/plugin.min.js) via windowManager.openUrl.
// Uses AccessibleTreeWidget (javascript/accessibletree.js) in single-select
// mode to list course folders/items; the Insert Link button then posts the
// selected item's href/type/id back to the parent window via postMessage.
require_once "../init.php";
require_once "../includes/courselinkinc.php";

$cid = Sanitize::courseId($_GET['cid']);
$preselected = isset($_GET['selected']) ? Sanitize::simpleString($_GET['selected']) : '';
$opennewwindow = !empty($_GET['newwindow']);

if (!isset($teacherid) && !isset($tutorid)) {
    echo "You need to log in as a teacher to access this page";
    exit;
}

$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id' => $cid));
$itemorder = $stm->fetchColumn();
$items = unserialize($itemorder);
if ($items === false) {
    $items = array();
}

$itemtypes = array();
$stm = $DBH->prepare("SELECT id,itemtype,typeid FROM imas_items WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $itemtypes[$row[0]] = array($row[1], $row[2]);
}

$iteminfo = array();
$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $iteminfo['Assessment'][$row[0]] = $row[1];
}
$stm = $DBH->prepare("SELECT id,title FROM imas_inlinetext WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $iteminfo['InlineText'][$row[0]] = $row[1];
}
$stm = $DBH->prepare("SELECT id,title FROM imas_linkedtext WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $iteminfo['LinkedText'][$row[0]] = $row[1];
}
$stm = $DBH->prepare("SELECT id,name FROM imas_forums WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $iteminfo['Forum'][$row[0]] = $row[1];
}
$stm = $DBH->prepare("SELECT id,name FROM imas_wikis WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $iteminfo['Wiki'][$row[0]] = $row[1];
}
$stm = $DBH->prepare("SELECT id,name FROM imas_drillassess WHERE courseid=:courseid");
$stm->execute(array(':courseid' => $cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $iteminfo['Drill'][$row[0]] = $row[1];
}

// Builds the small type-badge icon shown next to each tree entry, mirroring
// quickview()'s icon logic in courseshowitems.php: a site-configured
// $CFG['CPS']['miniicons'][key] image if set, else a colored letter badge
// using the same letters quickview uses (B/?/!/F/W/D).
function courseLinkTypeIcon($type)
{
    global $CFG, $staticroot;
    static $map = array(
        'Block' => 'folder',
        'Assessment' => 'assess',
        'InlineText' => 'inline',
        'LinkedText' => 'linked',
        'Forum' => 'forum',
        'Wiki' => 'wiki',
        'Drill' => 'drill',
    );
    if (!isset($map[$type])) {
        return '';
    }
    $key = $map[$type];
    if (!empty($CFG['CPS']['miniicons'][$key])) {
        return htmlspecialchars($staticroot . '/img/' . $CFG['CPS']['miniicons'][$key], ENT_QUOTES);
    }
    return '';
}

// Builds a nested array in the shape AccessibleTreeWidget expects
// ({id, label, icon, children}), one node per folder/item. Each node's id is
// the same "itemtype.typeid" (or "Block.blockid") string copyiteminc.php
// already uses to key $itemtypemap/$blockidmap, so it doubles as a unique
// tree id. href/ctype/realid ride along on the node for the Insert Link
// button to read back out of $courseLinkItems (built alongside, see below).
function buildCourseLinkTree($items, $cid)
{
    global $itemtypes, $iteminfo, $courseLinkItems;
    $out = array();
    if (!is_array($items)) {
        return $out;
    }
    foreach ($items as $item) {
        if (is_array($item)) {
            $blockid = intval($item['id']);
            $treeid = 'Block' . $blockid;
            $href = getCourseItemUrl('Block', $cid, $blockid);
            $node = array(
                'id' => $treeid,
                'label' => $item['name'],
                'icon' => courseLinkTypeIcon('Block'),
            );
            $children = buildCourseLinkTree($item['items'], $cid);
            if (count($children) > 0) {
                $node['children'] = $children;
            }
            $courseLinkItems[$treeid] = array('href' => $href, 'ctype' => 'Block', 'realid' => $blockid, 'label' => $item['name']);
            $out[] = $node;
        } else {
            if (!isset($itemtypes[$item])) {
                continue;
            }
            list($itemtype, $typeid) = $itemtypes[$item];
            $typeid = intval($typeid);
            if ($itemtype === 'Calendar' || !isset($iteminfo[$itemtype][$typeid])) {
                continue; // no viewer page to link to
            }
            $href = getCourseItemUrl($itemtype, $cid, $typeid);
            if ($href === false) {
                continue;
            }
            $treeid = $itemtype . $typeid;
            $label = $iteminfo[$itemtype][$typeid];
            $courseLinkItems[$treeid] = array('href' => $href, 'ctype' => $itemtype, 'realid' => $typeid, 'label' => $label);
            $out[] = array(
                'id' => $treeid,
                'label' => $label,
                'icon' => courseLinkTypeIcon($itemtype),
            );
        }
    }
    return $out;
}

$courseLinkItems = array();
$treedata = buildCourseLinkTree($items, $cid);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo _('Insert Course Link'); ?></title>
<script src="<?php echo $staticroot; ?>/javascript/accessibletree.js?v=072126"></script>
<link rel="stylesheet" href="<?php echo $staticroot; ?>/javascript/accessibletree.css?v=072126" type="text/css" />
<style>
    /* body fills the dialog's iframe exactly (no outer scrollbar); only
       #clRoot scrolls internally, so there's a single scrollbar, not two. */
    html, body { height: 100%; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; margin: 0; display: flex; flex-direction: column; box-sizing: border-box; }
    p.clHelp { flex: 0 0 auto; margin: 8px 8px 4px 8px; color: #444; }
    #clRoot { flex: 1 1 auto; overflow: auto; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; margin: 0; }
    .clOptions { flex: 0 0 auto; margin: 6px 8px; }
    .clOptions label { cursor: pointer; }
    .clTypeIcon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: bold;
        line-height: 1;
        color: #fff;
    }
</style>
</head>
<body>
<p class="clHelp"><?php echo _('Select a folder or item below, then click Insert Link.'); ?></p>
<div id="clRoot"></div>
<div class="clOptions">
    <label><input type="checkbox" id="clNewWindow"<?php echo $opennewwindow ? ' checked' : ''; ?>> <?php echo _('Open in new window'); ?></label>
</div>
<script>
// The "Insert Link" button lives in the TinyMCE dialog's own footer (added by
// tinymce8/plugins/courselink/plugin.min.js), not on this page. Clicking it
// sends this iframe a {type:'courselink-getselection'} message via the
// dialog API's sendMessage(); we reply with the selected item's data wrapped
// in TinyMCE's mceAction:'customAction' envelope, same convention already
// used by the mathquill plugin's equation palette.
var courseLinkItems = <?php echo json_encode($courseLinkItems, JSON_INVALID_UTF8_IGNORE); ?>;
var courseLinkTreeData = <?php echo json_encode($treedata, JSON_INVALID_UTF8_IGNORE); ?>;
var clCid = <?php echo intval($cid); ?>;
var clPreselectedId = <?php echo json_encode($preselected); ?>;
var clSelectedId = null;
var clTreeWidget = new AccessibleTreeWidget(document.getElementById('clRoot'), courseLinkTreeData, {
    selectionMode: 'single',
    selectableItems: 'all',
    onSelectionChange: function(selectedIds) {
        clSelectedId = selectedIds.length ? selectedIds[0] : null;
    }
});
if (clPreselectedId && courseLinkItems[clPreselectedId]) {
    // re-selecting expands the tree down to the item but doesn't fire
    // onSelectionChange, so track it directly too
    clTreeWidget.setSelectedItems([clPreselectedId]);
    clSelectedId = clPreselectedId;
}
window.addEventListener('message', function(evt) {
    if (evt.origin !== window.location.origin) { return; }
    if (!evt.data || evt.data.type !== 'courselink-getselection') { return; }
    if (!clSelectedId || !courseLinkItems[clSelectedId]) { return; }
    var info = courseLinkItems[clSelectedId];
    window.parent.postMessage({
        mceAction: 'customAction',
        data: {
            href: info.href,
            type: info.ctype,
            cid: clCid,
            id: info.realid,
            text: info.label,
            target: document.getElementById('clNewWindow').checked ? '_blank' : ''
        }
    }, window.location.origin);
});
</script>
</body>
</html>
