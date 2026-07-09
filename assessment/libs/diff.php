<?php  

require_once __DIR__ . '/../../includes/diff.php';

array_push(
    $GLOBALS['allowedmacros'],
    'diff',
    'diffbychar',
    'diffbycharhtml'
);

function diffbychar($old,$new) {
    $d = diff(str_split($old), str_split($new));
    // seems to put empty edits at the start and/or end sometimes; strip those
    foreach ($d as $k=>$v) {
        if (is_array($v) && empty($v['d']) && empty($v['i'])) {
            unset($d[$k]);
        }
    }
    return array_values($d);
}

function diffbycharhtml($old,$new,$format=3) {
    // format: 1: wrap ins, 2: wrap dec, 3: wrap both 
    $diff = diff(str_split($old), str_split($new));
    $ret = '';
	foreach($diff as $k){
		if(is_array($k))
			$ret .= ((!empty($k['d']) && $format&2)?"<del>".implode('',$k['d'])."</del>":'').
				((!empty($k['i']) && $format&1)?"<ins>".implode('',$k['i'])."</ins>":'');
		else $ret .= $k;
	}
	return $ret;
}