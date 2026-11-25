<?php

namespace IMathAS\assess2\selftest;

require_once __DIR__ . '/../AssessStandalone.php';
require_once __DIR__ . '/CorrectInputBuilder.php';

use AssessStandalone;
use IMathAS\assess2\questions\ScoreEngine;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

/**
 * Checks that a question template renders and grades on a set of seeds, before it is saved.
 *
 * Contract:
 *   run($template, $seeds, $checkScoring) returns ['ok' => bool, 'results' => list<SeedResult>] where
 *   SeedResult = ['seed', 'ok', 'render_errors', 'warnings', 'score_errors', 'raw', 'skipped_parts', 'ms'].
 *   A seed fails on any render or score error that is not a PHP warning/notice, and, when $checkScoring,
 *   on any part that does not score 1 (> .98) for its correct answer. Skipped parts (drawings, manual
 *   grading) never fail. Warnings are reported and never fail.
 *   $template: ['qtype' => string, 'control' => string, 'qtext' => string, 'solution' => string].
 *
 * Example:
 *   (new TemplateSelfTest($DBH))->run(['qtype' => 'number', 'control' => '$answer=3', 'qtext' => '$answerbox',
 *       'solution' => ''], [1, 2], true)
 *   returns ['ok' => true, 'results' => [['seed' => 1, 'ok' => true, 'raw' => [1], ...], ...]].
 */
final class TemplateSelfTest
{
    const QN = 27;
    const PASS_SCORE = .98;
    const MAX_SCORE_PASSES = 4;
    const WARNING_PATTERN = '/^Caught (warning|notice) in the question code/i';

    private $dbh;

    public function __construct($dbh)
    {
        $this->dbh = $dbh;
    }

    public function run(array $template, array $seeds, bool $checkScoring): array
    {
        $results = [];
        foreach ($seeds as $seed) {
            $results[] = $this->runSeed($template, (int) $seed, $checkScoring);
        }
        $ok = count(array_filter($results, function ($r) { return !$r['ok']; })) === 0;
        return ['ok' => $ok, 'results' => $results];
    }

    private function runSeed(array $template, int $seed, bool $checkScoring): array
    {
        $start = microtime(true);
        $result = ['seed' => $seed, 'ok' => false, 'render_errors' => [], 'warnings' => [],
            'score_errors' => [], 'raw' => [], 'skipped_parts' => [], 'ms' => 0];
        $line = self::questionData($template);

        $_SESSION['choicemap'] = [];
        ob_start();
        try {
            $a2 = new AssessStandalone($this->dbh);
            $a2->setQuestionData(self::QN, $line);
            $a2->setState(self::state($seed));
            $a2->displayQuestion(self::QN, ['showans' => false, 'showallparts' => false, 'printformat' => true]);
            $question = $a2->getQuestion();
            $messages = $question->getErrors();
        } catch (\Throwable $t) {
            $question = null;
            $messages = ['Caught error while rendering this question: ' . $t->getMessage()];
        }
        $printed = trim(ob_get_clean());
        if ($printed !== '') {
            $messages[] = 'Question code printed output: ' . mb_substr(strip_tags($printed), 0, 500);
        }
        list($result['render_errors'], $result['warnings']) = self::split($messages);

        if ($checkScoring && $question !== null && $result['render_errors'] === []) {
            $this->scoreSeed($line, $seed, $_SESSION['choicemap'][0] ?? [],
                $question->getCorrectAnswersForParts(), $result);
        }

        $result['ok'] = $result['render_errors'] === [] && $result['score_errors'] === []
            && count(array_filter($result['raw'], function ($raw) { return $raw <= self::PASS_SCORE; })) === 0;
        $result['ms'] = (int) round((microtime(true) - $start) * 1000);
        // Keyed by part index: always a JSON object, also when empty or numbered from 0.
        $result['raw'] = (object) $result['raw'];
        $result['skipped_parts'] = (object) $result['skipped_parts'];
        return $result;
    }

    /**
     * Scores the correct answer of every part. The first pass, with no answers, gives the evaluated variables;
     * each later pass submits the next candidate input of the parts that have not scored 1 yet.
     */
    private function scoreSeed(array $line, int $seed, array $choicemap, array $displayed, array &$result): void
    {
        if ($line['qtype'] === 'conditional') {
            $result['skipped_parts'][0] = 'conditional questions check answers with their own code';
            return;
        }
        list($vars, $errors) = $this->score($line, $seed, []);
        $multi = $line['qtype'] === 'multipart';
        $anstypes = $multi ? ($vars['anstypes'] ?? []) : [$line['qtype']];
        if ($multi && $anstypes === []) {
            $result['score_errors'] = array_merge(['Multipart question has no $anstypes'], self::split($errors)[0]);
            return;
        }

        $parts = [];
        foreach ($anstypes as $pn => $anstype) {
            $qnRef = $multi ? (self::QN + 1) * 1000 + $pn : self::QN;
            ob_start(); // engine helpers echo their complaints
            $built = CorrectInputBuilder::forPart($anstype, $vars, $multi, $pn, $qnRef,
                $choicemap[$qnRef] ?? null, (string) ($displayed[$pn] ?? ''));
            ob_end_clean();
            if ($built['skip'] !== null) {
                $result['skipped_parts'][$pn] = $built['skip'];
                continue;
            }
            $parts[$pn] = ['candidates' => $built['candidates'] ?: [[]], 'next' => 0, 'post' => [], 'raw' => 0];
        }

        for ($pass = 0; $pass < self::MAX_SCORE_PASSES; $pass++) {
            $post = [];
            $tried = false;
            foreach ($parts as $pn => &$part) {
                if ($part['raw'] <= self::PASS_SCORE && $part['next'] < count($part['candidates'])) {
                    $part['post'] = $part['candidates'][$part['next']++];
                    $tried = true;
                }
                $post += $part['post'];
            }
            unset($part);
            if (!$tried) {
                break;
            }
            list(, $errors, $raw) = $this->score($line, $seed, $post);
            foreach ($parts as $pn => &$part) {
                $part['raw'] = max($part['raw'], (float) ($raw[$pn] ?? 0));
            }
            unset($part);
        }

        foreach ($parts as $pn => $part) {
            $result['raw'][$pn] = $part['raw'];
        }
        list($blocking, $warnings) = self::split($errors);
        $result['score_errors'] = $blocking;
        $result['warnings'] = array_values(array_unique(array_merge($result['warnings'], $warnings)));
    }

    /** One ScoreEngine pass with $post as the submitted form; returns [vars, errors, raw scores by part]. */
    private function score(array $line, int $seed, array $post): array
    {
        $_POST = $post;
        $params = new ScoreQuestionParams();
        $params->setUserRights($GLOBALS['myrights'] ?? 0)
            ->setRandWrapper($GLOBALS['RND'])
            ->setQuestionNumber(self::QN)
            ->setQuestionData($line)
            ->setAssessmentId(0)
            ->setDbQuestionSetId(self::QN)
            ->setQuestionSeed($seed)
            ->setGivenAnswer($post['qn' . self::QN] ?? '')
            ->setAttemptNumber(0)
            ->setAllQuestionAnswers([])
            ->setAllQuestionAnswersAsNum([])
            ->setPartsToScore(true)
            ->setQnpointval(1);
        // ScoreEngine closes output buffers down to the level it started at, one more than its own:
        // give it one to close.
        ob_start();
        try {
            $scoreResult = (new ScoreEngine($this->dbh, $GLOBALS['RND']))->scoreQuestion($params);
        } catch (\Throwable $t) {
            return [[], ['Caught error while scoring this question: ' . $t->getMessage()], []];
        }
        return [$params->getVarsForScorePart(), $scoreResult['errors'] ?? [], $scoreResult['rawScores'] ?? []];
    }

    /** Splits engine messages into [blocking errors, warnings]. */
    private static function split(array $messages): array
    {
        $errors = [];
        $warnings = [];
        foreach ($messages as $message) {
            $message = trim((string) $message);
            if ($message === '') {
                continue;
            }
            if (preg_match(self::WARNING_PATTERN, $message)) {
                $warnings[] = $message;
            } else {
                $errors[] = $message;
            }
        }
        return [array_values(array_unique($errors)), array_values(array_unique($warnings))];
    }

    private static function questionData(array $template): array
    {
        return [
            'id' => self::QN, 'uniqueid' => 0, 'ownerid' => 1, 'adddate' => 0, 'lastmoddate' => time(),
            'qtype' => $template['qtype'], 'control' => $template['control'], 'qcontrol' => '',
            'qtext' => $template['qtext'], 'answer' => '', 'solution' => $template['solution'] ?? '',
            'extref' => '', 'hasimg' => 0, 'solutionopts' => 6, 'isrand' => 1,
        ];
    }

    private static function state(int $seed): array
    {
        $qn = self::QN;
        return [
            'seeds' => [$qn => $seed],
            'qsid' => [$qn => $qn],
            'stuanswers' => [],
            'stuanswersval' => [],
            'scorenonzero' => [($qn + 1) => -1],
            'scoreiscorrect' => [($qn + 1) => -1],
            'partattemptn' => [$qn => []],
            'rawscores' => [$qn => []],
        ];
    }
}
