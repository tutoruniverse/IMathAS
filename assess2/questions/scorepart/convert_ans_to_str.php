<?php
//Convert answer information into string

function convert_to_str_ans(array $ans_info, $pixtox, $pixtoy, $type) {

    $mh = $pixtox(floatval($ans_info[0]));
    $mk = $pixtoy(floatval($ans_info[1]));
    $mx = $pixtox(floatval($ans_info[2]));
    $my = $pixtoy(floatval($ans_info[3]));
           
    if($type === "line") {
        $ma = ($my - $mk) / ($mx - $mh);
        $mb = $mk - ($ma * $mh);
        $l_range = sprintf("x = %f -> %f, ", $mh, $mx);
        if($ans_info[4] == "closedpolygon"){
            $l_type = "closed polygon";
        }
        else {
            $l_type = "polygon";
        }
        if ($mb >= 0) {
            $sign1 = "+";
        } else {
            $sign1 = "-";
            $mb = abs($mb);
        }
        $ans = sprintf("This %s includes a line: y = %f * x %s %f from %s", $l_type, $ma, $sign1, $mb, $l_range);
        
        return array($ans); 
    }

    elseif ($type == "vecs") {
        if($ans_info[4] === 'r') {
            $v_type = "ray";
            $v_range = sprintf("x = %f, ", $mh);
        }
        elseif ($ans_info[4] === 'ls') {
            $v_type = "line segment";
            $v_range = sprintf("x = %f -> %f, ", $mh, $mx);
        }
        else {
            $v_type = "vector";
            $v_range = sprintf("x = %f -> %f, ", $mh, $mx);
        }
        $ma = ($my - $mk) / ($mx - $mh);
        $mb = $mk - ($ma * $mh);
        if ($mb >= 0) {
            $sign1 = "+";
        } else {
            $sign1 = "-";
            $mb = abs($mb);
        }
        $ans = sprintf("This includes a %s function: y = %f * x %s %f from %s", $v_type, $ma, $sign1, $mb, $v_range);

        return array($ans); 
    }

    elseif ($type == "circs") {
        $ma = floatval(($mx - $mh)*($mx - $mh) + ($my-$mk)*($my-$mk));
        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }
        if ($mk >= 0) {
            $sign2 = "-";
        } else {
            $sign2 = "+";
            $mk = abs($mk);
        }
        $ans = sprintf("This includes function: (x %s %f)^2 + (y %s %f)^2 = %f", $sign1, $mh, $sign2, $mk, $ma);

        return array($ans);    
    }

    elseif ($type == "parabs") {
        $ma = floatval(($my - $mk) / (($mx-$mh)*($mx-$mh)));

        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "+";
        } else {
            $sign2 = "-";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: y = %f * (x %s %f)^2 %s %f", $ma, $sign1, $mh, $sign2, $mk);

        return array($ans);    
    }

    elseif ($type == "hparabs") {
        $ma = floatval(($my - $mk) / (($mx-$mh)*($mx-$mh)));
        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "+";
        } else {
            $sign2 = "-";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: x = %f * (y %s %f)^2 %s %f", $ma, $sign1, $mh, $sign2, $mk);

        return array($ans); 
    }

    elseif ($type == "sqrts") {
        $ma = floatval(($my - $mk) / sqrt($flip * ($mx - $mh)));
        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "+";
        } else {
            $sign2 = "";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: y = %f * sqrt(x %s %f) %s %f", $ma, $sign1, $mh, $sign2, $mk);

        return array($ans); 
    }

    elseif ($type == "cubics") {
        $ma = floatval(safepow($my-$mk, 1/3)/($mx-$mh));
        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "+";
        } else {
            $sign2 = "-";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: y = %f * (x %s %f)^3 %s %f", $ma, $sign1, $mh, $sign2, $mk);
        return array($ans); 
    }

    elseif ($type == "cuberoots") {
        $ma = floatval(safepow($my-$mk, 3)/($mx-$mh));
        if ($mh >= 0) {
            $sign1 = "+";
        } else {
            $sign1 = "";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "-";
        } else {
            $sign2 = "+";
            $mk = abs($mk);
        }
        $ans = sprintf("This includes function: x = (1 / %f) * (y %s %f)^3 %s %f", $ma, $sign2, $mk, $sign1, $mh);
        return array($ans);
    }

    elseif ($type == "ellipses") {
        $ma = floatval(abs($mx - $mh));
        $mb = floatval(abs($my - $mk));
        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "-";
        } else {
            $sign2 = "+";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: ((1 / %f) * (x %s %f))^2 + ((1 / %f) * (y %s %f))^2 = 1", $ma, $sign1, $mh, $mb, $sign2, $mk);

        return array($ans); 
    }

    elseif ($type == "hhyperbolas") {
        $ma = floatval(abs($mx - $mh));
        $mb = floatval(abs($my - $mk));
        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "-";
        } else {
            $sign2 = "+";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: ((1 / %f) * (x %s %f))^2 - ((1 / %f) * (y %s %f))^2 = 1", $ma, $sign1, $mh, $mb, $sign2, $mk);

        return array($ans); 
    }

    elseif ($type == "vhyperbolas") {
        $ma = floatval(abs($mx - $mh));
        $mb = floatval(abs($my - $mk));

        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "-";
        } else {
            $sign2 = "+";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: ((1 / %f) * (y %s %f))^2 - ((1 / %f) * (x %s %f))^2 = 1", $mb, $sign2, $mk, $ma, $sign1, $mh);

        return array($ans); 
    }

    elseif ($type == "abs") {
        if ($mh==$mx) {
            $ma = 0;
        } else {
            $ma = ($my-$mk)/($mx-$mh);
            if ($mh > $mx) {
                $ma *= -1;
            }
        }

        if ($mh >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mh = abs($mh);
        }

        if ($mk >= 0) {
            $sign2 = "+";
        } else {
            $sign2 = "-";
            $mk = abs($mk);
        }

        $ans = sprintf("This includes function: y = %f * abs(x %s %f) %s %f", $ma, $sign1, $mh, $sign2, $mk);

        return array($ans); 
    }

    elseif ($type == "exps") {
        $m4 = $pixtox(floatval($ans_info[4]));
        $m5 = $pixtoy(floatval($ans_info[5]));
        $xop = $pixtox(floatval($ans_info[7]));
        $yop = $pixtoy(floatval($ans_info[8]));

        if ($ans_info[6] == 8.3) {
            $horizasy = $yop;
            $adjy2 = $horizasy - $my;
            $adjy1 = $horizasy - $mk;
            $Lx1p = $mh;
            $Lx2p = $mx;
        } else if ($ans_info[6]==8.5) {
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
            
            if ($str >= 0) {
                $sign1 = "+";
            } else {
                $sign1 = "-";
                $str = abs($str);
            }

            if ($xop >= 0) {
                $sign2 = "-";
            } else {
                $sign2 = "+";
                $xop = abs($xop);
            }

            $ans = sprintf("This includes function: y = %f %s %f * %f^(x %s %f)", $horizasy, $sign1, $str, $base, $sign2, $xop);

            return array($ans); 
        }
    }

    elseif ($type == "logs") {
        $m4 = $pixtox(floatval($ans_info[4]));
        $m5 = $pixtoy(floatval($ans_info[5]));
        $xop = $pixtox(floatval($ans_info[7]));
        $yop = $pixtoy(floatval($ans_info[8]));

        if ($ans_info[6] == 8.4) {
            $vertasy = $xop;
            $adjx2 = $vertasy - $mx;
            $adjx1 = $vertasy - $mh;
            $Ly1p = $mk;
            $Ly2p = $my;
        } else if ($ans_info[6]==8.6) {
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

            if ($str >= 0) {
                $sign1 = "+";
            } else {
                $sign1 = "-";
                $str = abs($str);
            }

            if ($yop >= 0) {
                $sign2 = "-";
            } else {
                $sign2 = "+";
                $yop = abs($yop);
            }

            $ans = sprintf("This includes function: x = %f %s %f * %f^(y %s %f)", $vertasy, $isgn1, $str, $base, $sign2, $yop);

            return array($ans); 
        }
    }

    elseif ($type == "rats") {
        $ma = ($mx-$mh)*($my-$mk);

        if ($ma >= 0) {
            $sign1 = "+";
        } else {
            $sign1 = "-";
            $ma = abs($ma);
        }

        if ($mh >= 0) {
            $sign2 = "-";
        } else {
            $sign2 = "+";
            $mh = abs($mh);
        }

        $ans = sprintf("This includes function: y = %f %s %f / (x %s %f)", $mk, $sign1, $ma, $sign2, $mh);

        return array($ans); 
    }

    elseif ($type == "coss") {
        $ma = (max($my,$mk)-min($my,$mk))/2;
        $mb = pi()/(abs($mx - $mh));
        $mc = max($mx,$mh);
        $md = ($my + $mk) / 2;

        if ($mc >= 0) {
            $sign1 = "-";
        } else {
            $sign1 = "+";
            $mc = abs($mc);
        }

        if ($ma >= 0) {
            $sign2 = "+";
        } else {
            $sign2 = "-";
            $ma = abs($ma);
        }

        $ans = sprintf("This includes function: y = %f %s %f * cos(%f * (x - %f))", $md, $sign2, $ma, $mb, $sign1, $mc);

        return array($ans); 
    }
    
    elseif ($type == "lines") {
        $ma = ($my - $mk) / ($mx - $mh);
        $mb = $mk - ($ma * $mh);

        if ($mb >= 0) {
            $sign1 = "+";
        } else {
            $sign1 = "-";
            $mb = abs($mb);
        }

        $ans = sprintf("This includes function: y = %f * x %s %f", $ma, $sign1, $mb);

        return array($ans); 
    }

    elseif ($type == "dots") {
        $ans = sprintf("There is a dot at: (%f, %f)", $mh, $mk);

        return array($ans); 
    }

    elseif ($type == "odots") {
        $ans = sprintf("There is an open dot at: (%f, %f)", $mh, $mk);

        return array($ans); 
    }

    elseif ($type == "ineqlines") {
        $in_type = floatval($ans_info[6]);
        $mx2 = $pixtox(floatval($ans_info[4]));
        $my2 = $pixtoy(floatval($ans_info[5]));
        error_log($my2);
        if($in_type < floatval(10.3)) {
            error_log($in_type);
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

            if ($mh >= 0) {
                $sign1 = "-";
            } else {
                $sign1 = "+";
                $mh = abs($mh);
            }

            if ($mk >= 0) {
                $sign2 = "+";
            } else {
                $sign2 = "-";
                $mk = abs($mk);
            }

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

            if ($mh >= 0) {
                $sign1 = "-";
            } else {
                $sign1 = "+";
                $mh = abs($mh);
            }

            if ($mk >= 0) {
                $sign2 = "+";
            } else {
                $sign2 = "-";
                $mk = abs($mk);
            }

            $ans = sprintf("This includes function: y %s%s %f * (x %s %f)^2 + %f", $l_dir, $l_drt, $ma, $sign1, $mh, $sign2, $mk);

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

            if ($mh >= 0) {
                $sign1 = "-";
            } else {
                $sign1 = "+";
                $mh = abs($mh);
            }

            if ($mk >= 0) {
                $sign2 = "+";
            } else {
                $sign2 = "-";
                $mk = abs($mk);
            }

            $ans = sprintf("This includes function: y %s%s %f * abs(x %s %f) %s %f", $l_dir, $l_drt, $ma, $sign1, $mh, $sign2, $mk);

            return array($ans); 
        }
    }

    elseif ($type == "line1d") {
        $head = min($mh, $mx);
        $tail = max($mh, $mx);
        $ans = sprintf("There is a line from: %f to %f", $head, $tail);

        return array($ans); 
    }

    elseif ($type == "dots1d") {
        $ans = sprintf("There is a dot at: %f", $mh);

        return array($ans); 
    }

    elseif ($type == "odots1d") {
        $ans = sprintf("There is an open dot at: %f", $mh);

        return array($ans);
    }
}

