<?php

require_once "../init.php";
require_once "interpret5.php";

if ($myrights < 100) {
    exit;
}

echo '<style>.resG { color: #0c0;} .resB { color #c00; }</style>';
$tests = json_decode('[
{"in":"$a=0\n$a = 1 if $b==2\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif ($b==2) { $a=1 ; };\n$c=1;\n;"},
{"in":"$a=0\n$a = 1 if ($b==2)\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (($b==2)) { $a=1 ; };\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) {$b = 1}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (($a==0)) { {$b=1;} ;};\n$c=1;\n;"},
{"in":"$a=0\nif $a==0 {$b = 1}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif ($a==0) { {$b=1;} ;};\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) $b = 1\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (($a==0)) { $b=1 ;};\n$c=1;\n;"},
{"in":"$a=0\nif $a==0 $b = 1\n$c=1","out":"error;"},
{"in":"$a=0\nif ($a==0) $b = 1; $c = 1\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (($a==0)) { $b=1 ;};\n$c=1;\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) $b = 1 else if ($a==1) $b = 2 else $b =3\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (($a==0)) { $b=1 ;} else if (($a==1)) { $b=2 ;} else { $b=3 };\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) $b = 1 elseif ($a==1) $b = 2 else $b =3\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (($a==0)) { $b=1 ;} else if (($a==1)) { $b=2 ;} else { $b=3 };\n$c=1;\n;"},
{"in":"$a=0\nif $a==0 {$b = 1 } else if $a==1 {$b = 2 } else {$b = 3}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif ($a==0) { {$b=1;} ;} else if ($a==1) { {$b=2;} ;} else { {$b=3;} };\n$c=1;\n;"},
{"in":"if ($a+3)\/4==3 { $c = 1 }","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;if (($a+3)\/4==3) { {$c=1;} ;};\n;"},
{"in":"if ($a+3)\/4==3 $c = 1 ","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;if (($a+3)) { \/4==3*$c=1 ;};\n;"},
{"in":"{$a=0\n$b = 1 } if $c==1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;if ($c==1) { {$a=0;\n$b=1;} ; };\n;"},
{"in":"$a=0\n for ($i=0..2) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (is_nan(2) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(2),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {{$a++;};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n$c=1;\n;"},
{"in":"$a=0\n for ($i=0..2) $a++\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (is_nan(2) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(2),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {$a++;};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n$c=1;\n;"},
{"in":"$a=0\n for $i=0..2 {$a++}\n$c=1","out":"error;"},
{"in":"$a=0\n foreach ($arr as $k=>$v) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($arr)){$arr=null;}if (!isset($k)){$k=null;}if (!isset($v)){$v=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (!is_array($arr)) {echo \'input of foreach must be an array\';} else {\r\n                        $forloopcnt[1]=0; \r\n                        foreach ($arr as $k=>$v) { \r\n                            $forloopcnt[1]++;\r\n                            if ($forloopcnt[1]==1000) { break; }\r\n                            { {$a++;} ;}\r\n                        }; \r\n                        if ($forloopcnt[1]>=1000) {echo \"foreach loop exceeded 1000 iterations - giving up\";}};\n$c=1;\n;"},
{"in":"$a=0\n foreach ($arr as $k=>$v) $a++\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($arr)){$arr=null;}if (!isset($k)){$k=null;}if (!isset($v)){$v=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (!is_array($arr)) {echo \'input of foreach must be an array\';} else {\r\n                        $forloopcnt[1]=0; \r\n                        foreach ($arr as $k=>$v) { \r\n                            $forloopcnt[1]++;\r\n                            if ($forloopcnt[1]==1000) { break; }\r\n                            { $a++ ;}\r\n                        }; \r\n                        if ($forloopcnt[1]>=1000) {echo \"foreach loop exceeded 1000 iterations - giving up\";}};\n$c=1;\n;"},
{"in":"$a=0\n foreach $arr as $k=>$v {$a++}\n$c=1","out":"error;"},
{"in":"$a=0\n$a = 1 where $a%2==0\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\n$wherecount[0]=0;$wherecount[1]=0;do{$wherecount[1]++;$wherecount[0]++;$a=1;} while (!($a%2==0) && $wherecount[1]<200 && $wherecount[0]<1000); if ($wherecount[1]==200) {echo \"where not met in 200 iterations\";}; if ($wherecount[0]>=1000 && $wherecount[0]<2000 ) {echo \"nested where not met in 1000 iterations\";};\n$c=1;\n;"},
{"in":"$a=0\n$a = 1 where ($a%2==0)\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\n$wherecount[0]=0;$wherecount[1]=0;do{$wherecount[1]++;$wherecount[0]++;$a=1;} while (!(($a%2==0)) && $wherecount[1]<200 && $wherecount[0]<1000); if ($wherecount[1]==200) {echo \"where not met in 200 iterations\";}; if ($wherecount[0]>=1000 && $wherecount[0]<2000 ) {echo \"nested where not met in 1000 iterations\";};\n$c=1;\n;"},
{"in":"{$a=0\n$b = 1 } where $c==1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$wherecount[0]=0;$wherecount[1]=0;do{$wherecount[1]++;$wherecount[0]++;{$a=0;\n$b=1;};} while (!($c==1) && $wherecount[1]<200 && $wherecount[0]<1000); if ($wherecount[1]==200) {echo \"where not met in 200 iterations\";}; if ($wherecount[0]>=1000 && $wherecount[0]<2000 ) {echo \"nested where not met in 1000 iterations\";};\n;"},
{"in":"  for ($i=0..20) { for ($j=0..11) { $a[] = $j }}","out":"if (!isset($i)){$i=null;}if (!isset($j)){$j=null;}if (!isset($a)){$a=null;}$wherecount[0]=0;$whilecount[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {{if (is_nan(11) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($j=(int)ceil(round(floatval(0),4)),$forloopcnt[2]=0;$j<=(int)floor(round(floatval(11),4)) && $forloopcnt[2]<1000; $j++, $forloopcnt[2]++) {{$a[]=$j;};};\r\n\t\t\t\t\t\tif ($forloopcnt[2]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n;"},
{"in":"$a=0\nfor ($i=2..0) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nif (is_nan(0) || is_nan(2)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(2),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(0),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {{$a++;};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n$c=1;\n;"},
{"in":"$a=0\nfor ($i=0;$i<5;$i++) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$a=0;\nfor ($i=0,$forloopcnt[1]=0;($i<5) && $forloopcnt[1]<1000; ($i++), $forloopcnt[1]++) {{$a++;};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"  for ($i=0..20) { if $i==4 { $b = $i }}","out":"if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {{if ($i==4) { {$b=$i;} ;};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n;"},
{"in":"  for ($i=0..20) { if ($i==4) { $b = $i }}","out":"if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {{if (($i==4)) { {$b=$i;} ;};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n;"},
{"in":"  for ($i=0..20) { if ($i==4)  $b = $i }","out":"if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000; $i++, $forloopcnt[1]++) {{if (($i==4)) { $b=$i ;};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};\n;"},
{"in":"if ($a==1) || $a==5 { $b = 1 } else if ($a==2) || ($a==3) { $b = 2 } else { $b = 3}","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if (($a==1)||$a==5) { {$b=1;} ;} else if (($a==2)||($a==3)) { {$b=2;} ;} else { {$b=3;} };\n;"},
{"in":"if $a==1 {$b=1} else if $a==2 {$b=2} else if $a==3 {$b=3} else {$b=4}","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if ($a==1) { {$b=1;} ;} else if ($a==2) { {$b=2;} ;} else if ($a==3) { {$b=3;} ;} else { {$b=4;} };\n;"},
{"in":"if ($a==1) $b=1 else if ($a==2) $b=2 else if ($a==3) $b=3 else $b=4","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if (($a==1)) { $b=1 ;} else if (($a==2)) { $b=2 ;} else if (($a==3)) { $b=3 ;} else { $b=4 };\n;"},
{"in":"if $a==1 { $b = 1 } else if ($a==2) || ($a==3) { $b = 2 } else { $b = 3}","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;if ($a==1) { {$b=1;} ;} else if (($a==2)||($a==3)) { {$b=2;} ;} else { {$b=3;} };\n;"}

]', true);

foreach ($tests as $k=>$item) {
    if ($item["out"]=="error;") { // suppress expected errors
        ob_start();
    }
    $out = interpret("control", '', $item["in"]);
    if ($item["out"]=="error;") { // suppress expected errors
        ob_clean();
    }
    $res = ($item['out'] == $out)? 'G' : 'B';
    echo '<code class="res'.$res.'">'.$item['in'].'</code><br>';
}


$newtests = json_decode('[
    {"in":"if $a==1 {$b=1} else if $a==2 {$b=2} else if $a==3 {$b=3} else {$b=4}"},
    {"in":"if ($a==1) $b=1 else if ($a==2) $b=2 else if ($a==3) $b=3 else $b=4"}
]', true);


foreach ($newtests as $k=>$item) {
    ob_start();
    $out = interpret("control", '', $item["in"]);
    ob_clean();
    $newtests[$k]['out'] = $out;
}

echo '<pre>';
foreach ($newtests as $k) {
echo str_replace("'", "\\'", json_encode($k)).",\n";
}
echo '<br/>';