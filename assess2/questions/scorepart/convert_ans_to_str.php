<?php
//Convert answer information into string

function fmt($n) {
    return rtrim(rtrim(sprintf('%f', $n), '0'), '.');
}

/** Coefficient prefix: '', '-', '2 * ', '-2 * ' — omits '1 *' and '-1 *'. */
function fmt_coeff($a) {
    $fabs = fmt(abs($a));
    if ($fabs === '1') {
        return $a < 0 ? '-' : '';
    }
    return ($a < 0 ? '-' : '') . $fabs . ' * ';
}

/** Inner expression without outer parens: 'x', 'x - 3', 'x + 2'. */
function fmt_inner($h, $var = 'x') {
    $fh = fmt(abs($h));
    if ($fh === '0') return $var;
    return $h > 0 ? "$var - $fh" : "$var + $fh";
}

/** (x - h) with outer parens when needed: 'x', '(x - 3)', '(x + 2)'. */
function fmt_xterm($h, $var = 'x') {
    $inner = fmt_inner($h, $var);
    return $inner === $var ? $var : "($inner)";
}

/** Vertical offset: '', ' + 3', ' - 2' — omits '+ 0' and '- 0'. */
function fmt_kterm($k) {
    $fk = fmt(abs($k));
    if ($fk === '0') return '';
    return $k > 0 ? " + $fk" : " - $fk";
}

/** (x-h)^2/a^2 in standard form: 'x^2', 'x^2 / 9', '(x - 2)^2', '(x - 2)^2 / 9'. */
function fmt_squared_term($h, $a, $var = 'x') {
    $base = fmt_xterm($h, $var) . '^2';
    $fa2 = fmt($a * $a);
    if ($fa2 === '1') return $base;
    return "$base / $fa2";
}

/**
 * Trig function argument: b*(x-h).
 * Returns 'x', 'x - 2', '2 * x', '2 * (x - 3)', etc.
 * b is assumed positive (as it always is for cos/tan).
 */
function fmt_trig_arg($b, $h) {
    $inner = fmt_inner($h);   // 'x', 'x - 2', 'x + 1'
    $fb = fmt($b);
    if ($fb === '1') return $inner;
    if ($inner === 'x') return "$fb * x";
    return "$fb * ($inner)";
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

    $line_eq = fmt($ma) === '0' ? "y = " . fmt($mb) : "y = " . fmt_coeff($ma) . "x" . fmt_kterm($mb);
    $ans = "This $l_type includes a line: $line_eq from $l_range";

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

    $line_eq = fmt($ma) === '0' ? "y = " . fmt($mb) : "y = " . fmt_coeff($ma) . "x" . fmt_kterm($mb);
    $ans = "This includes a $v_type: $line_eq from $v_range";

    return array($ans);
}

function fans_circs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(($mx - $mh)*($mx - $mh) + ($my-$mk)*($my-$mk));

    $ans = "This includes a circle: " . fmt_xterm($mh) . "^2 + " . fmt_xterm($mk, 'y') . "^2 = " . fmt($ma);

    return array($ans);
}

function fans_parabs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(($my - $mk) / (($mx-$mh)*($mx-$mh)));

    $ans = "This includes a parabola: y = " . fmt_coeff($ma) . fmt_xterm($mh) . "^2" . fmt_kterm($mk);

    return array($ans);
}

function fans_hparabs ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(($mx - $mh) / (($my-$mk)*($my-$mk)));

    $ans = "This includes a horizontal parabola: x = " . fmt_coeff($ma) . fmt_xterm($mk, 'y') . "^2" . fmt_kterm($mh);

    return array($ans);
}

function fans_sqrts ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $flip = ($mx < $mh) ? -1 : 1;
    $ma = floatval(($my - $mk) / sqrt($flip * ($mx - $mh)));

    $ans = "This includes a square root function: y = " . fmt_coeff($ma) . "sqrt(" . fmt_inner($mh) . ")" . fmt_kterm($mk);

    return array($ans);
}

function fans_cubics ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(safepow($my-$mk, 1/3)/($mx-$mh));

    $fabs_a = fmt(abs($ma));
    $sign_a = $ma < 0 ? '-' : '';
    if ($fabs_a === '1') {
        $xterm = fmt_xterm($mh);
        $base = ($ma < 0 && $xterm !== 'x') ? "(-$xterm)" : ($ma < 0 ? '(-x)' : $xterm);
    } elseif (fmt(abs($mh)) === '0') {
        $base = "({$sign_a}{$fabs_a} * x)";
    } else {
        $inner = fmt_inner($mh);
        $base = "({$sign_a}{$fabs_a} * ($inner))";
    }
    $ans = "This includes a cubic function: y = {$base}^3" . fmt_kterm($mk);
    return array($ans);
}

function fans_cuberoots ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(safepow($my-$mk, 3)/($mx-$mh));

    $fa = fmt(abs($ma));
    $sign_a = $ma < 0 ? '-' : '';
    $yterm = fmt_xterm($mk, 'y');
    $cube = $sign_a . $yterm . '^3' . ($fa === '1' ? '' : " / $fa");
    $ans = "This includes a cube root function: x = $cube" . fmt_kterm($mh);
    return array($ans);
}

function fans_ellipses ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    $ans = "This includes an ellipse: " . fmt_squared_term($mh, $ma) . " + " . fmt_squared_term($mk, $mb, 'y') . " = 1";

    return array($ans);
}

function fans_hhyperbolas ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    $ans = "This includes a horizontal hyperbola: " . fmt_squared_term($mh, $ma) . " - " . fmt_squared_term($mk, $mb, 'y') . " = 1";

    return array($ans);
}

function fans_vhyperbolas ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = floatval(abs($mx - $mh));
    $mb = floatval(abs($my - $mk));

    $ans = "This includes a vertical hyperbola: " . fmt_squared_term($mk, $mb, 'y') . " - " . fmt_squared_term($mh, $ma) . " = 1";

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

    $ans = "This includes an absolute value function: y = " . fmt_coeff($ma) . "abs(" . fmt_inner($mh) . ")" . fmt_kterm($mk);

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

        $exp_x = fmt($xop) === '0' ? 'x' : "x $sign2 " . fmt($xop);
        $fh = fmt($horizasy);
        if ($fh === '0') {
            $coeff_str = ($sign1 === '-' ? '-' : '') . fmt($str);
            $ans = sprintf("This includes an exponential function: y = %s * %s^(%s)", $coeff_str, fmt($base), $exp_x);
        } else {
            $ans = sprintf("This includes an exponential function: y = %s %s %s * %s^(%s)", $fh, $sign1, fmt($str), fmt($base), $exp_x);
        }

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

        $exp_y = fmt($yop) === '0' ? 'y' : "y $sign2 " . fmt($yop);
        $fv = fmt($vertasy);
        if ($fv === '0') {
            $coeff_str = ($sign1 === '-' ? '-' : '') . fmt($str);
            $ans = sprintf("This includes a logarithmic function: x = %s * %s^(%s)", $coeff_str, fmt($base), $exp_y);
        } else {
            $ans = sprintf("This includes a logarithmic function: x = %s %s %s * %s^(%s)", $fv, $sign1, fmt($str), fmt($base), $exp_y);
        }

        return array($ans);
    }
}

function fans_rats ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = ($mx-$mh)*($my-$mk);

    $inner = fmt_inner($mh);
    $denom = $inner === 'x' ? 'x' : "($inner)";
    $ans = "This includes a rational function: y = " . fmt($ma) . " / $denom" . fmt_kterm($mk);

    return array($ans);
}

function fans_coss ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = (abs($my - $mk))/2;
    $mb = pi()/(abs($mx - $mh));
    $mc = ($mh + $mx + (($mk > $my) - ($mk < $my)) * ($mh - $mx)) / 2;
    $md = ($my + $mk) / 2;

    $ans = "This includes a cosine function: y = " . fmt_coeff($ma) . "cos(" . fmt_trig_arg($mb, $mc) . ")" . fmt_kterm($md);

    return array($ans);
}

function fans_sins ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    // mh,mk = zero crossing (midline); mx,my = peak or trough
    $ma = $my - $mk;  // signed amplitude
    $mb = M_PI / (2 * abs($mx - $mh));  // B = π / (2 * quarter-period)
    $md = $mk;  // vertical shift = midline

    $ans = "This includes a sine function: y = " . fmt_coeff($ma) . "sin(" . fmt_trig_arg($mb, $mh) . ")" . fmt_kterm($md);

    return array($ans);
}

function fans_tan ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $amp = $my - $mk;
    $b = M_PI / (4 * abs($mx - $mh));

    $ans = "This includes a tangent function: y = " . fmt_coeff($amp) . "tan(" . fmt_trig_arg($b, $mh) . ")" . fmt_kterm($mk);

    return array($ans);
}

function fans_lines ($mh, $mk, $mx, $my, $pixtox, $pixtoy) {

    list($mh, $mk, $mx, $my) = clean_up(array($mh, $mk, $mx, $my));
    list(list($mh, $mx), list($mk, $my)) = change_to_math(array($mh, $mx), array($mk, $my), $pixtox, $pixtoy);

    $ma = ($my - $mk) / ($mx - $mh);
    $mb = $mk - ($ma * $mh);

    $ans = fmt($ma) === '0'
        ? "This includes a line: y = " . fmt($mb)
        : "This includes a line: y = " . fmt_coeff($ma) . "x" . fmt_kterm($mb);

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
            $ans = sprintf("This includes an inequality: x %s%s %s", $l_dir, $l_drt, fmt($mh));

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

        $ans = sprintf("This includes an inequality: y %s%s %s * (x %s %s) %s %s", $l_dir, $l_drt, fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

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

        $ans = sprintf("This includes an inequality: y %s%s %s * (x %s %s)^2 %s %s", $l_dir, $l_drt, fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

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

        $ans = sprintf("This includes an inequality: y %s%s %s * abs(x %s %s) %s %s", $l_dir, $l_drt, fmt($ma), $sign1, fmt($mh), $sign2, fmt($mk));

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

