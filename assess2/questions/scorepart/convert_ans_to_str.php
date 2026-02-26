<?php
//Convert answer information into string

function add_sign($num, $sign) {
    if ($num >= 0) {
        $sign = "+";
    } else {
        $sign = "-";
        $num = abs($num);
    }
    return [$num, $sign];
}

function minus_sign($num, $sign) {
    if ($num >= 0) {
        $sign = "-";
    } else {
        $sign = "+";
        $num = abs($num);
    }
    return [$num, $sign];
}

function clean_up($marray) {
    foreach ($marray as $key => $value) {
        $marray[$key] = preg_replace("/[^0-9.\-]/", "", $value);
    }
    return $marray;
}

function change_to_math($xarray, $yarray, $pixtox, $pixtoy) {
    foreach ($xarray as $key => $value) {
        $xarray[$key] = $pixtox(floatval($value));
    }
    foreach ($yarray as $key => $value) {
        $yarray[$key] = $pixtoy(floatval($value));
    }
    return [$xarray, $yarray];
}

        
function fans_polygons($mh, $mk, $mx, $my, $type, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = ($my - $mk) / ($mx - $mh);
    $mb = $mk - ($ma * $mh);
    $l_range = sprintf("x = %f -> %f. ", $mh, $mx);
    if($type == "closedpolygon"){
        $l_type = "closed polygon";
    }
    else {
        $l_type = "polygon";
    }

    list($mb, $sign1) = add_sign($mb, $sign1);

    $ans = sprintf("This %s includes a line: y = %f * x %s %f from %s", $l_type, $ma, $sign1, $mb, $l_range);
    
    return array($ans); 
}

function fans_vecs($mh, $mk, $mx, $my, $type, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    if($type === 'r') {
        $v_type = "ray";
        $v_range = sprintf("x = %f, ", $mh);
    }
    elseif ($type === 'ls') {
        $v_type = "line segment";
        $v_range = sprintf("x = %f -> %f, ", $mh, $mx);
    }
    else {
        $v_type = "vector";
        $v_range = sprintf("x = %f -> %f, ", $mh, $mx);
    }
    $ma = ($my - $mk) / ($mx - $mh);
    $mb = $mk - ($ma * $mh);

    list($mb, $sign1) = add_sign($mb, $sign1);

    $ans = sprintf("This includes a %s function: y = %f * x %s %f from %s", $v_type, $ma, $sign1, $mb, $v_range);

    return array($ans); 
}

function fans_circs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(($mx - $mh)*($mx - $mh) + ($my-$mk)*($my-$mk));

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: (x %s %f)^2 + (y %s %f)^2 = %f", $sign1, $mh, $sign2, $mk, $ma);

    return array($ans);    
}

function fans_parabs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(($my - $mk) / (($mx-$mh)*($mx-$mh)));

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %f * (x %s %f)^2 %s %f", $ma, $sign1, $mh, $sign2, $mk);

    return array($ans);    
}

function fans_hparabs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(($my - $mk) / (($mx-$mh)*($mx-$mh)));

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: x = %f * (y %s %f)^2 %s %f", $ma, $sign1, $mh, $sign2, $mk);

    return array($ans); 
}

function fans_sqrts ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $flip = ($mx < $mh) ? -1 : 1;
    $ma = floatval(($my - $mk) / sqrt($flip * ($mx - $mh)));

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %f * sqrt(x %s %f) %s %f", $ma, $sign1, $mh, $sign2, $mk);

    return array($ans); 
}

function fans_cubics ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(safepow($my-$mk, 1/3)/($mx-$mh));
    
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = (%f * (x %s %f))^3 %s %f", $ma, $sign1, $mh, $sign2, $mk);
    return array($ans); 
}

function fans_cuberoots ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(safepow($my-$mk, 3)/($mx-$mh));

    list($mh, $sign1) = add_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: x = (1 / %f) * (y %s %f)^3 %s %f", $ma, $sign2, $mk, $sign1, $mh);
    return array($ans);
}

function fans_ellipses ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: ((1 / %f) * (x %s %f))^2 + ((1 / %f) * (y %s %f))^2 = 1", $ma, $sign1, $mh, $mb, $sign2, $mk);

    return array($ans); 
}

function fans_hhyperbolas ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));
    
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: ((1 / %f) * (x %s %f))^2 - ((1 / %f) * (y %s %f))^2 = 1", $ma, $sign1, $mh, $mb, $sign2, $mk);

    return array($ans); 
}

function fans_vhyperbolas ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: ((1 / %f) * (y %s %f))^2 - ((1 / %f) * (x %s %f))^2 = 1", $mb, $sign2, $mk, $ma, $sign1, $mh);

    return array($ans); 
}

function fans_abs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    if ($mh==$mx) {
        $ma = 0;
    } else {
        $ma = ($my-$mk)/($mx-$mh);
        if ($mh > $mx) {
            $ma *= -1;
        }
    }

    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %f * abs(x %s %f) %s %f", $ma, $sign1, $mh, $sign2, $mk);

    return array($ans); 
}

function fans_exps ($mh, $mk, $mx, $my, $m4, $m5, $type, $xop, $yop, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my, $m4, $m5, $xop, $yop) = clean_up(array($mh, $mk, $mx, $my, $m4, $m5, $xop, $yop));
    list(list($mh, $mx, $m4, $xop), list($mk, $my, $m5, $yop)) = change_to_math(array($mh, $mx, $m4, $xop), array($mk, $my, $m5, $yop), $pixtox, $pixtoy);

    if ($type == 8.3) {
        $horizasy = $yop;
        $adjy2 = $horizasy - $my;
        $adjy1 = $horizasy - $mk;
        $Lx1p = $mh;
        $Lx2p = $mx;
    } else if ($type ==8.5) {
        $horizasy = $mk;
        $adjy2 = $horizasy - $m5;
        $adjy1 = $horizasy - $my;
        $Lx1p = $mx;
        $Lx2p = $m4;
    }
    if ($adjy1*$adjy2>0 && $Lx1p!=$Lx2p) {
        $base = safepow($adjy2/$adjy1,1/($Lx2p-$Lx1p));
        if (abs($Lx1p-$xop)<abs($Lx2p-$xop)) {
            $str = $adjy1/safepow($base,$Lx1p-$xop);
        } else {
            $str = $adjy2/safepow($base,$Lx2p-$xop);
        }
        $str *= -1;
        
        list($str, $sign1) = add_sign($str, $sign1);
        list($xop, $sign2) = minus_sign($xop, $sign2);

        $ans = sprintf("This includes function: y = %f %s %f * %f^(x %s %f)", $horizasy, $sign1, $str, $base, $sign2, $xop);

        return array($ans); 
    }
}

function fans_logs ($mh, $mk, $mx, $my, $m4, $m5, $type, $xop, $yop, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my, $m4, $m5, $xop, $yop) = clean_up(array($mh, $mk, $mx, $my, $m4, $m5, $xop, $yop));
    list(list($mh, $mx, $m4, $xop), list($mk, $my, $m5, $yop)) = change_to_math(array($mh, $mx, $m4, $xop), array($mk, $my, $m5, $yop), $pixtox, $pixtoy);

    if ($type == 8.4) {
        $vertasy = $xop;
        $adjx2 = $vertasy - $mx;
        $adjx1 = $vertasy - $mh;
        $Ly1p = $mk;
        $Ly2p = $my;
    } else if ($type==8.6) {
        $vertasy = $mh;
        $adjx2 = $vertasy - $m4;
        $adjx1 = $vertasy - $mx;
        $Ly1p = $my;
        $Ly2p = $m5;
    }
    if ($adjx1*$adjx2>0 && $Ly1p!=$Ly2p) {
        $base = safepow($adjx2/$adjx1,1/($Ly2p-$Ly1p));
        if (abs($pts[2]-$yop)<abs($Ly2p-$yop)) {
            $str = $adjx1/safepow($base,$Ly1p-$yop);
        } else {
            $str = $adjx2/safepow($base,$Ly2p-$yop);
        }
        $str *= -1;

        list($str, $sign1) = add_sign($str, $sign1);
        list($yop, $sign2) = minus_sign($yop, $sign2);

        $ans = sprintf("This includes function: x = %f %s %f * %f^(y %s %f)", $vertasy, $sign1, $str, $base, $sign2, $yop);

        return array($ans); 
    }
}

function fans_rats ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = ($mx-$mh)*($my-$mk);

    list($ma, $sign1) = add_sign($ma, $sign1);
    list($mh, $sign2) = minus_sign($mh, $sign2);

    $ans = sprintf("This includes function: y = %f %s %f / (x %s %f)", $mk, $sign1, $ma, $sign2, $mh);

    return array($ans); 
}

function fans_coss ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = (abs($my - $mk))/2;
    $mb = pi()/(abs($mx - $mh));
    $mc = ($mh + $mx + (($mk > $my) - ($mk < $my)) * ($mh - $mx)) / 2;
    $md = ($my + $mk) / 2;

    list($mc, $sign1) = minus_sign($mc, $sign1);
    list($ma, $sign2) = add_sign($ma, $sign2);

    $ans = sprintf("This includes function: y = %f %s %f * cos(%f * (x %s %f))", $md, $sign2, $ma, $mb, $sign1, $mc);

    return array($ans); 
}

function fans_lines ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {
    
    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);
    
    $ma = ($my - $mk) / ($mx - $mh);
    $mb = $mk - ($ma * $mh);

    list($mb, $sign1) = add_sign($mb, $sign1);

    $ans = sprintf("This includes function: y = %f * x %s %f", $ma, $sign1, $mb);

    return array($ans); 
}

function fans_dots ($mh, $mk, $pixtox, $pixtoy) {

    list($mh, $mk) = clean_up(array($mh, $mk));
    list(list($mh), list($mk)) = change_to_math(array($mh), array($mk), $pixtox, $pixtoy);

    list($mh, $mk) = clean_up(array($mh, $mk), $pixtox, $pixtoy);

    $ans = sprintf("There is a dot at: (%f, %f)", $mh, $mk);

    return array($ans); 
}

function fans_odots ($mh, $mk, $pixtox, $pixtoy) {

    list($mh, $mk) = clean_up(array($mh, $mk));
    list(list($mh), list($mk)) = change_to_math(array($mh), array($mk), $pixtox, $pixtoy);

    $ans = sprintf("There is an open dot at: (%f, %f)", $mh, $mk);

    return array($ans); 
}

function fans_ineqlines ($mh, $mk, $mx, $my, $mx2, $my2, $type, $pixtox, $pixtoy) {
    
    list($mh, $mk, $mx, $my, $mx2, $my2) = clean_up(array($mh, $mk, $mx, $my, $mx2, $my2));
    list(list($mh, $mx, $mx2), list($mk, $my, $my2)) = change_to_math(array($mh, $mx, $mx2), array($mk, $my, $my2), $pixtox, $pixtoy);
    
    $in_type = floatval($type);
    if($in_type < floatval(10.3)) {
        if ($in_type === floatval(10)) {
            $l_drt = '=';
        }
        if ($mx === $mh) {
            if ($mx2 > $mh) { 
                $l_dir = '<';
            }
            else {
                $l_dir = '>';
            }
            $ans = sprintf("This includes function: x %s%s %f", $l_dir, $l_drt, $mh);

            return array($ans); 
        }

        $ma = ($my - $mk) / ($mx - $mh);
        $ynew = $ma * ($mx2 - $mh) + $mk;
        if ($my2 < $ynew) {
            $l_dir = '<';
        }
        else {
            $l_dir = '>';
        }

        list($mh, $sign1) = minus_sign($mh, $sign1);
        list($mk, $sign2) = add_sign($mk, $sign2);

        $ans = sprintf("This includes function: y %s%s %f * (x %s %f) %s %f", $l_dir, $l_drt, $ma, $sign1, $mh, $sign2, $mk);

        return array($ans); 
    }
    elseif ($in_type < floatval(10.5)) {
        $ma = ($my - $mk) / (($mx - $mh) * ($mx - $mh));
        $ynew = $ma * ($mx2 - $mh) * ($mx2 - $mh) + $mk;
        if($my2 < $ynew) {
            $l_dir = '<';
        }
        else {
            $l_dir = '>';
        }
        if($in_type === floatval(10.3)) {
            $l_drt = '=';
        }

        list($mh, $sign1) = minus_sign($mh, $sign1);
        list($mk, $sign2) = add_sign($mk, $sign2);

        $ans = sprintf("This includes function: y %s%s %f * (x %s %f)^2 %s %f", $l_dir, $l_drt, $ma, $sign1, $mh, $sign2, $mk);

        return array($ans); 
    }
    else {
        $ma = ($my - $mk) / ($mx - $mh);
        if ($mx < $mh) {
            $ma *= -1;
        }

        $ynew = $ma * abs($mx2 - $mh) + $mk;
        if($my2 < $ynew) {
            $l_dir = '<';
        }
        else {
            $l_dir = '>';
        }
        if($in_type === floatval(10.5)) {
            $l_drt = '=';
        }
        else {
            $l_drt = '';
        }

        list($mh, $sign1) = minus_sign($mh, $sign1);
        list($mk, $sign2) = add_sign($mk, $sign2);

        $ans = sprintf("This includes function: y %s%s %f * abs(x %s %f) %s %f", $l_dir, $l_drt, $ma, $sign1, $mh, $sign2, $mk);

        return array($ans); 
    }
}

function fans_line1d ($mh, $mx, $pixtox, $pixtoy) {
    list($mh, $mx) = clean_up(array($mh, $mx), $pixtox, $pixtoy);

    list($mh, $mx) = clean_up(array($mh, $mx));
    list(list($mh, $mx), ) = change_to_math(array($mh, $mx), array(), $pixtox, $pixtoy);


    $head = min($mh, $mx);
    $tail = max($mh, $mx);
    $ans = sprintf("There is a line from: %f to %f", $head, $tail);

    return array($ans); 
}

function fans_dots1d ($mh, $pixtox, $pixtoy) {

    list($mh) = clean_up(array($mh));
    list(list($mh), ) = change_to_math(array($mh), array(), $pixtox, $pixtoy);

    $ans = sprintf("There is a dot at: %f", $mh);

    return array($ans); 
}

function fans_odots1d ($mh, $pixtox, $pixtoy) {

    list($mh) = clean_up(array($mh));
    list(list($mh), ) = change_to_math(array($mh), array(), $pixtox, $pixtoy);

    $ans = sprintf("There is an open dot at: %f", $mh);

    return array($ans);
}

