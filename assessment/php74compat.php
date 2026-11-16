<?php
// PHP 7.4 value semantics for operations that PHP 8 turned into a TypeError / DivisionByZeroError.
// Use only where a question template can pass a wrong type and PHP 7.4 carried on with a value:
// questions must render and grade exactly as they did on PHP 7.4.
// Every return value below was checked against PHP 7.4.19 CLI.

// count(): PHP 7.4 gave 0 for null and 1 for any other non-countable value (with a warning).
//   count74(null) === 0; count74(5) === 1; count74([1, 2]) === 2
function count74($v) {
	if (is_array($v) || $v instanceof Countable) {
		return count($v);
	}
	return $v === null ? 0 : 1;
}

// Arithmetic operand: PHP 7.4 read a non-numeric string as its leading number, or 0 if it has none.
//   num74("`3`") === 0; num74("3abc") === 3; num74("2.5x") === 2.5; num74(4) === 4
function num74($v) {
	// PHP 8 also accepts trailing whitespace ("4 ") as numeric; parse it below so both versions agree.
	if (!is_string($v) || (is_numeric($v) && !preg_match('/\s$/', $v))) {
		return $v;
	}
	if (!preg_match('/^\s*[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/', $v, $m)) {
		return 0;
	}
	return preg_match('/[.eE]/', $m[0]) ? (float) $m[0] : (int) $m[0];
}

// $a / $b: PHP 7.4 returned INF, -INF or NAN for a division by zero (with a warning).
//   div74(5, 0) === INF; div74(-5, 0) === -INF; is_nan(div74(0, 0)); div74(6, 3) === 2
function div74($a, $b) {
	$a = num74($a);
	$b = num74($b);
	if ($b == 0) {
		if ($a > 0) {
			return INF;
		}
		return $a < 0 ? -INF : NAN;
	}
	return $a / $b;
}
