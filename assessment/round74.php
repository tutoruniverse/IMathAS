<?php
// round74(): PHP 7.4 round() results on PHP 8.4. Kept in its own file so the round() -> round74()
// rewrite of the libraries never touches the round() calls inside it.
if (!function_exists('round74')) {
/**
 * round() with PHP 7.4 results, usable on PHP 8.4 (8.4 rewrote round() and dropped pre-rounding).
 * Port of _php_math_round() from PHP 7.4 ext/standard/math.c, mode PHP_ROUND_HALF_UP only
 * (iMathAS templates call round() with one or two arguments).
 * On PHP 7.4 it returns exactly what round() returns (checked on 400k values, see r74_dump.php).
 *
 * Example: round74(1.4149999999999998, 2) === 1.42 on both 7.4 and 8.4 (8.4 round() gives 1.41).
 */
function round74($value, $places = 0) {
    $value = (float) $value;
    $places = (int) $places;
    if (!is_finite($value) || $value == 0.0) {
        return $value;
    }
    $precision_places = 14 - (int) floor(log10(abs($value)));
    $f1 = round74_pow10(abs($places));
    if ($precision_places > $places && $precision_places - 15 < $places) {
        $use_precision = max(-60, $precision_places);              // -(4 * DBL_DIG)
        $tmp = round74_helper(round74_basic($value, $use_precision));
        $use_precision = max(-60, $places - $use_precision);
        $tmp = $tmp / round74_pow10(abs($use_precision));
    } else {
        $tmp = $places >= 0 ? $value * $f1 : $value / $f1;
        if (abs($tmp) >= 1e15) {
            return $value;                                          // beyond float precision
        }
    }
    $tmp = round74_helper($tmp);
    if (abs($places) < 23) {
        return $places > 0 ? $tmp / $f1 : $tmp * $f1;
    }
    $back = (float) sprintf('%15fe%d', $tmp, -$places);
    return is_finite($back) ? $back : $value;
}

function round74_pow10($power) {
    return ($power < 0 || $power > 22) ? pow(10.0, $power) : (float) (10 ** $power);
}

function round74_basic($value, $places) {
    $f1 = round74_pow10(abs($places));
    return $places >= 0 ? $value * $f1 : $value / $f1;
}

function round74_helper($value) {
    return $value >= 0.0 ? floor($value + 0.5) : ceil($value - 0.5);
}
}
