<?php
//Convert answer information into string

function fmt($n) {
    return rtrim(rtrim(sprintf('%f', $n), '0'), '.');
}

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
    $l_range = sprintf("x = %s -> %s. ", fmt($mh), fmt($mx));
    if($type == "closedpolygon"){
        $l_type = "closed polygon";
    }
    else {
        $l_type = "polygon";
    }

    $sign1 = '';
    list($mb, $sign1) = add_sign($mb, $sign1);

    $ans = sprintf("This %s includes a line: y = %s * x %s %s from %s", $l_type, fmt($ma), $sign1, fmt($mb), $l_range);

    return array($ans);
}

function fans_vecs($mh, $mk, $mx, $my, $type, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    if($type === 'r') {
        $v_type = "ray";
        $v_range = sprintf("x = %s, ", fmt($mh));
    }
    elseif ($type === 'ls') {
        $v_type = "line segment";
        $v_range = sprintf("x = %s -> %s, ", fmt($mh), fmt($mx));
    }
    else {
        $v_type = "vector";
        $v_range = sprintf("x = %s -> %s, ", fmt($mh), fmt($mx));
    }
    $ma = ($my - $mk) / ($mx - $mh);
    $mb = $mk - ($ma * $mh);

    $sign1 = '';
    list($mb, $sign1) = add_sign($mb, $sign1);

    $ans = sprintf("This includes a %s function: y = %s * x %s %s from %s", $v_type, fmt($ma), $sign1, fmt($mb), $v_range);

    return array($ans);
}

function fans_circs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(($mx - $mh)*($mx - $mh) + ($my-$mk)*($my-$mk));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: (x %s %s)^2 + (y %s %s)^2 = %s", $sign1, fmt($mh), $sign2, fmt($mk), fmt($ma));

    return array($ans);
}

function fans_parabs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(($my - $mk) / (($mx-$mh)*($mx-$mh)));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %s * (x %s %s)^2 %s %s", fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

    return array($ans);
}

function fans_hparabs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(($mx - $mh) / (($my-$mk)*($my-$mk)));

    $sign1 = ''; $sign2 = '';
    list($mk, $sign1) = minus_sign($mk, $sign1);
    list($mh, $sign2) = add_sign($mh, $sign2);

    $ans = sprintf("This includes function: x = %s * (y %s %s)^2 %s %s", fmt($ma), $sign1, fmt($mk), $sign2, fmt($mh));

    return array($ans);
}

function fans_sqrts ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $flip = ($mx < $mh) ? -1 : 1;
    $ma = floatval(($my - $mk) / sqrt($flip * ($mx - $mh)));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %s * sqrt(x %s %s) %s %s", fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

    return array($ans);
}

function fans_cubics ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(safepow($my-$mk, 1/3)/($mx-$mh));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = (%s * (x %s %s))^3 %s %s", fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));
    return array($ans);
}

function fans_cuberoots ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(safepow($my-$mk, 3)/($mx-$mh));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = add_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: x = (1 / %s) * (y %s %s)^3 %s %s", fmt($ma), $sign2, fmt($mk), $sign1, fmt($mh));
    return array($ans);
}

function fans_ellipses ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: ((1 / %s) * (x %s %s))^2 + ((1 / %s) * (y %s %s))^2 = 1", fmt($ma), $sign1, fmt($mh), fmt($mb), $sign2, fmt($mk));

    return array($ans);
}

function fans_hhyperbolas ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: ((1 / %s) * (x %s %s))^2 - ((1 / %s) * (y %s %s))^2 = 1", fmt($ma), $sign1, fmt($mh), fmt($mb), $sign2, fmt($mk));

    return array($ans);
}

function fans_vhyperbolas ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = minus_sign($mk, $sign2);

    $ans = sprintf("This includes function: ((1 / %s) * (y %s %s))^2 - ((1 / %s) * (x %s %s))^2 = 1", fmt($mb), $sign2, fmt($mk), fmt($ma), $sign1, fmt($mh));

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

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %s * abs(x %s %s) %s %s", fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

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

        $sign1 = ''; $sign2 = '';
        list($str, $sign1) = add_sign($str, $sign1);
        list($xop, $sign2) = minus_sign($xop, $sign2);

        $ans = sprintf("This includes function: y = %s %s %s * %s^(x %s %s)", fmt($horizasy), $sign1, fmt($str), fmt($base), $sign2, fmt($xop));

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

        $sign1 = ''; $sign2 = '';
        list($str, $sign1) = add_sign($str, $sign1);
        list($yop, $sign2) = minus_sign($yop, $sign2);

        $ans = sprintf("This includes function: x = %s %s %s * %s^(y %s %s)", fmt($vertasy), $sign1, fmt($str), fmt($base), $sign2, fmt($yop));

        return array($ans);
    }
}

function fans_rats ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = ($mx-$mh)*($my-$mk);

    $sign1 = ''; $sign2 = '';
    list($ma, $sign1) = add_sign($ma, $sign1);
    list($mh, $sign2) = minus_sign($mh, $sign2);

    $ans = sprintf("This includes function: y = %s %s %s / (x %s %s)", fmt($mk), $sign1, fmt($ma), $sign2, fmt($mh));

    return array($ans);
}

function fans_coss ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = (abs($my - $mk))/2;
    $mb = pi()/(abs($mx - $mh));
    $mc = ($mh + $mx + (($mk > $my) - ($mk < $my)) * ($mh - $mx)) / 2;
    $md = ($my + $mk) / 2;

    $sign1 = ''; $sign2 = '';
    list($mc, $sign1) = minus_sign($mc, $sign1);
    list($ma, $sign2) = add_sign($ma, $sign2);

    $ans = sprintf("This includes function: y = %s %s %s * cos(%s * (x %s %s))", fmt($md), $sign2, fmt($ma), fmt($mb), $sign1, fmt($mc));

    return array($ans);
}

function fans_tan ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $amp = $my - $mk;
    $b = M_PI / (4 * abs($mx - $mh));

    $sign1 = ''; $sign2 = '';
    list($mh, $sign1) = minus_sign($mh, $sign1);
    list($mk, $sign2) = add_sign($mk, $sign2);

    $ans = sprintf("This includes function: y = %s * tan(%s * (x %s %s)) %s %s", fmt($amp), fmt($b), $sign1, fmt($mh), $sign2, fmt($mk));

    return array($ans);
}

function fans_lines ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = ($my - $mk) / ($mx - $mh);
    $mb = $mk - ($ma * $mh);

    $sign1 = '';
    list($mb, $sign1) = add_sign($mb, $sign1);

    $ans = sprintf("This includes function: y = %s * x %s %s", fmt($ma), $sign1, fmt($mb));

    return array($ans);
}

function fans_dots ($mh, $mk, $pixtox, $pixtoy) {

    list($mh, $mk) = clean_up(array($mh, $mk));
    list(list($mh), list($mk)) = change_to_math(array($mh), array($mk), $pixtox, $pixtoy);

    $ans = sprintf("There is a dot at: (%s, %s)", fmt($mh), fmt($mk));

    return array($ans);
}

function fans_odots ($mh, $mk, $pixtox, $pixtoy) {

    list($mh, $mk) = clean_up(array($mh, $mk));
    list(list($mh), list($mk)) = change_to_math(array($mh), array($mk), $pixtox, $pixtoy);

    $ans = sprintf("There is an open dot at: (%s, %s)", fmt($mh), fmt($mk));

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
            $ans = sprintf("This includes function: x %s%s %s", $l_dir, $l_drt, fmt($mh));

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

        $sign1 = ''; $sign2 = '';
        list($mh, $sign1) = minus_sign($mh, $sign1);
        list($mk, $sign2) = add_sign($mk, $sign2);

        $ans = sprintf("This includes function: y %s%s %s * (x %s %s) %s %s", $l_dir, $l_drt, fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

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

        $sign1 = ''; $sign2 = '';
        list($mh, $sign1) = minus_sign($mh, $sign1);
        list($mk, $sign2) = add_sign($mk, $sign2);

        $ans = sprintf("This includes function: y %s%s %s * (x %s %s)^2 %s %s", $l_dir, $l_drt, fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

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

        $sign1 = ''; $sign2 = '';
        list($mh, $sign1) = minus_sign($mh, $sign1);
        list($mk, $sign2) = add_sign($mk, $sign2);

        $ans = sprintf("This includes function: y %s%s %s * abs(x %s %s) %s %s", $l_dir, $l_drt, fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

        return array($ans);
    }
}

function fans_line1d ($mh, $mx, $pixtox, $pixtoy) {
    list($mh, $mx) = clean_up(array($mh, $mx));
    list(list($mh, $mx), ) = change_to_math(array($mh, $mx), array(), $pixtox, $pixtoy);

    $head = min($mh, $mx);
    $tail = max($mh, $mx);
    $ans = sprintf("There is a line from: %s to %s", fmt($head), fmt($tail));

    return array($ans);
}

function fans_dots1d ($mh, $pixtox, $pixtoy) {

    list($mh) = clean_up(array($mh));
    list(list($mh), ) = change_to_math(array($mh), array(), $pixtox, $pixtoy);

    $ans = sprintf("There is a dot at: %s", fmt($mh));

    return array($ans);
}

function fans_odots1d ($mh, $pixtox, $pixtoy) {

    list($mh) = clean_up(array($mh));
    list(list($mh), ) = change_to_math(array($mh), array(), $pixtox, $pixtoy);

    $ans = sprintf("There is an open dot at: %s", fmt($mh));

    return array($ans);
}

