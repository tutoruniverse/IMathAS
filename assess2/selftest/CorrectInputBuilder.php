<?php

namespace IMathAS\assess2\selftest;

/**
 * Builds the inputs a student would submit to answer one question part correctly.
 *
 * Contract:
 *   forPart() returns ['candidates' => list<array<string,mixed>>, 'skip' => ?string].
 *   Each candidate is a set of POST fields (key => value) that answers the part correctly, best first;
 *   the caller scores the first one and falls back to the next only if it does not score 1.
 *   'skip' is a reason when the answer type cannot be answered automatically (drawings, manual grading);
 *   then 'candidates' is empty and the part must not count as a failure.
 *   An empty 'candidates' with no 'skip' means the correct answer could not be located, e.g. a choices
 *   $answer that is not one of the displayed options: the part then scores 0, which is the finding.
 *
 * Inputs:
 *   $vars       the question variables the score parts read (ScoreQuestionParams::getVarsForScorePart()).
 *   $choicemap  the display order the student saw: $_SESSION['choicemap'][0][$qnRef] after displayQuestion();
 *               a list of keys for choices/multans, [questionKeys, answerKeys] for matching.
 *   $displayed  the "show answer" text for the part (Question::getCorrectAnswersForParts()), used as a last
 *               candidate for text answers whose input format differs from the raw \$answer.
 *
 * Example:
 *   CorrectInputBuilder::forPart('choices', ['answer' => 0, 'questions' => ['red', 'blue']], false, 0, 27, [1, 0], '')
 *   returns ['candidates' => [['qn27' => '1']], 'skip' => null].
 */
final class CorrectInputBuilder
{
    const MATRIX_TYPES = ['matrix', 'calcmatrix', 'complexmatrix', 'algmatrix'];
    const TAKE_ANYTHING = ['takeanything', 'takeanythingorblank'];

    public static function forPart(string $anstype, array $vars, bool $multi, int $partnum, int $qnRef,
                                   $choicemap, string $displayed): array
    {
        $opt = function ($key, $arrayval = 0) use ($vars, $multi, $partnum) {
            return getOptionVal($vars, $key, $multi, $partnum, $arrayval);
        };
        $key = "qn$qnRef";

        switch ($anstype) {
            case 'draw':
                return self::skip('drawing answers cannot be generated automatically');
            case 'conditional':
                return self::skip('conditional questions check answers with their own code');
            case 'file':
                return self::skip('file answers need an upload');
            case 'essay':
                if (!in_array($opt('scoremethod'), self::TAKE_ANYTHING, true)) {
                    return self::skip('essay answers are graded manually');
                }
                return self::found([[$key => 'self-test answer']]);
            case 'choices':
                return self::found(self::choices($key, $opt('answer'), $choicemap));
            case 'multans':
                return self::found(self::multans($key, $opt('answers'), $opt('questions', 2), $choicemap));
            case 'matching':
                return self::found(self::matching($key, $opt('matchlist'), $choicemap));
        }

        $answer = $opt('answer');
        if (is_array($answer) || $answer === null) {
            return self::found([]);
        }
        $answer = (string) $answer;
        $values = [];
        if ($anstype === 'molecule') {
            $values[] = explode('~~~', $answer)[0];
        } else if (in_array($anstype, self::MATRIX_TYPES, true)) {
            $values[] = $answer;
        } else {
            foreach (explode(' or ', $answer) as $alt) {
                $values[] = trim($alt);
            }
        }
        $ansformats = array_map('trim', explode(',', (string) $opt('answerformat')));
        if (in_array($anstype, ['interval', 'calcinterval'], true) && in_array('inequality', $ansformats, true)) {
            $variable = $opt('variables');
            $values = array_map(function ($value) use ($variable) {
                return self::inequality($value, is_string($variable) && $variable !== '' ? $variable : 'x');
            }, $values);
        }
        $candidates = [];
        foreach ($values as $value) {
            foreach (self::textForms($anstype, $value, $ansformats) as $text) {
                $candidates[] = in_array($anstype, self::MATRIX_TYPES, true)
                    ? self::matrix($key, $text, $opt('answersize'))
                    : [$key => $text];
            }
        }
        $shown = self::plainText($displayed);
        if ($shown !== '' && !in_array($anstype, self::MATRIX_TYPES, true)) {
            $candidates[] = [$key => $shown];
        }
        if (in_array('nosoln', $ansformats, true) || in_array('nosolninf', $ansformats, true)) {
            if ($answer === 'DNE' || preg_match('/^no\s*solution/', $answer)) {
                $candidates[] = ["qs$qnRef" => 'DNE', $key => ''];
            } else if ($answer === 'oo' || preg_match('/^inf/', $answer)) {
                $candidates[] = ["qs$qnRef" => 'inf', $key => ''];
            }
        }
        return self::found(self::unique($candidates));
    }

    /**
     * Text a student could type for one correct value; the formats a calculated box requires, and a point
     * inside a number range such as "[2,5)".
     */
    private static function textForms(string $anstype, string $value, array $ansformats): array
    {
        $forms = [];
        $value = trim($value, '`');
        if ($anstype === 'number' && preg_match('/^[\[\(]\s*(-?[\d.]+)\s*,\s*(-?[\d.]+)\s*[\]\)]$/', $value, $m)) {
            $forms[] = (string) (($m[1] + $m[2]) / 2);
        }
        if ($anstype === 'calculated' && is_numeric($value)) {
            if (array_intersect(['mixednumber', 'sloppymixednumber', 'mixednumberorimproper'], $ansformats)) {
                $forms[] = decimaltofraction($value, 'mixednumber');
            } else if (array_intersect(['fraction', 'reducedfraction'], $ansformats)) {
                $forms[] = decimaltofraction($value);
            } else if (in_array('scinot', $ansformats, true)
                || (in_array('scinotordec', $ansformats, true) && (abs($value) > 1000 || abs($value) < .001))) {
                $forms[] = makescinot($value, -1, '*');
            }
        }
        $forms[] = $value;
        return $forms;
    }

    /**
     * Interval notation as the inequality a student types: "(-oo,2]" -> "x<=2", "[1,3)" -> "1 <= x < 3".
     * intervaltoineq() writes ASCIIMath (le, lt, ...) next to the variable; a placeholder variable keeps
     * those words apart from the variable's own letters.
     */
    private static function inequality(string $interval, string $variable): string
    {
        if (strpos($interval, '"') !== false) {
            return $interval;
        }
        $v = "\x01";
        $text = intervaltoineq($interval, $v);
        if (strpos($text, "$v != ") === 0) { // the scorers take "x<2 or x>2", not "x != 2"
            $point = substr($text, strlen("$v != "));
            $text = "$v<$point or $v>$point";
        }
        $words = [
            "{$v}le" => "$v<=", "{$v}lt" => "$v<", "{$v}ge" => "$v>=", "{$v}gt" => "$v>",
            " le $v" => " <= $v", " lt $v" => " < $v", "$v le " => "$v <= ", "$v lt " => "$v < ",
            ' \\ "or" \\ ' => ' or ',
        ];
        // str_replace, not strtr: in "3 le x lt 5" both words touch the same variable.
        $text = str_replace(array_keys($words), array_values($words), $text);
        return str_replace($v, $variable, $text);
    }

    private static function choices(string $key, $answer, $randkeys): array
    {
        if (!is_array($randkeys)) {
            return [];
        }
        $candidates = [];
        foreach (explode(' or ', (string) $answer) as $alt) {
            $pos = array_search(intval($alt), $randkeys);
            if ($pos !== false) {
                $candidates[] = [$key => (string) $pos];
            }
        }
        return $candidates;
    }

    /**
     * One candidate per " or " alternative: the display positions whose keys are in $answers are checked.
     * An empty $answers means "None of these", which the box appended as key count($questions).
     */
    private static function multans(string $key, $answers, $questions, $randkeys): array
    {
        if (!is_array($randkeys) || !is_array($questions)) {
            return [];
        }
        $candidates = [];
        foreach (explode(' or ', trim((string) $answers, ' ,')) as $alt) {
            $akeys = trim($alt) === '' ? [] : array_map('trim', explode(',', $alt));
            if ($akeys === [] && count($randkeys) > count($questions)) {
                $akeys = [count($questions)];
            }
            $checked = [];
            foreach ($randkeys as $pos => $k) {
                if (in_array($k, $akeys)) {
                    $checked[$pos] = $pos;
                }
            }
            $candidates[] = $checked === [] ? [] : [$key => $checked];
        }
        return $candidates;
    }

    private static function matching(string $key, $matchlist, $choicemap): array
    {
        if (!is_array($choicemap) || count($choicemap) !== 2) {
            return [];
        }
        list($randqkeys, $randakeys) = $choicemap;
        $matches = [];
        if (!empty($matchlist)) {
            foreach (explode(',', $matchlist) as $i => $entry) {
                $matches[$i] = trim(explode(' or ', $entry)[0]);
            }
        }
        $post = [];
        foreach ($randqkeys as $pos => $qkey) {
            $target = $matches === [] ? $qkey : ($matches[$qkey] ?? null);
            $apos = array_search($target, $randakeys);
            $post["$key-$pos"] = $apos === false ? '' : (string) $apos;
        }
        return [$post];
    }

    /** A sized matrix is typed cell by cell (row-major), an unsized one as a single entry. */
    private static function matrix(string $key, string $answer, $answersize): array
    {
        if ($answersize === null || $answersize === '') {
            return [$key => $answer];
        }
        list($cells) = parseMatrixToArray($answer);
        $post = [];
        foreach ($cells as $i => $cell) {
            $post["$key-$i"] = trim($cell);
        }
        return $post;
    }

    private static function plainText(string $displayed): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br/>', '<br>'], ' ', $displayed)));
        return trim(str_replace('`', '', $text));
    }

    private static function unique(array $candidates): array
    {
        $seen = [];
        $out = [];
        foreach ($candidates as $c) {
            $sig = serialize($c);
            if (!isset($seen[$sig])) {
                $seen[$sig] = true;
                $out[] = $c;
            }
        }
        return $out;
    }

    private static function found(array $candidates): array
    {
        return ['candidates' => $candidates, 'skip' => null];
    }

    private static function skip(string $reason): array
    {
        return ['candidates' => [], 'skip' => $reason];
    }
}
