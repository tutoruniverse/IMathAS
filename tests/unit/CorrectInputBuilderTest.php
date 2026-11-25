<?php

$GLOBALS['allowedmacros'] = $GLOBALS['allowedmacros'] ?? [];
require_once __DIR__ . '/../../i18n/i18n.php';
require_once __DIR__ . '/../../assess2/questions/answerboxhelpers.php';
require_once __DIR__ . '/../../assessment/macros/parsers.php';
require_once __DIR__ . '/../../assessment/macros/format.php';
require_once __DIR__ . '/../../assessment/macros/math.php';
require_once __DIR__ . '/../../assess2/selftest/CorrectInputBuilder.php';

use IMathAS\assess2\selftest\CorrectInputBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The inputs a student would submit for the correct answer, per answer type.
 * End-to-end scoring of these inputs is checked against the engine through selftest.php.
 */
final class CorrectInputBuilderTest extends TestCase
{
    private function first(string $anstype, array $vars, $choicemap = null, string $displayed = '',
                           bool $multi = false, int $partnum = 0, int $qnRef = 27): array
    {
        $built = CorrectInputBuilder::forPart($anstype, $vars, $multi, $partnum, $qnRef, $choicemap, $displayed);
        $this->assertNull($built['skip']);
        $this->assertNotEmpty($built['candidates']);
        return $built['candidates'][0];
    }

    public function testChoicesSubmitsTheDisplayPositionOfTheAnswerKey()
    {
        $this->assertSame(['qn27' => '2'], $this->first('choices', ['answer' => 0, 'questions' => ['a', 'b', 'c']], [1, 2, 0]));
    }

    public function testChoicesAnswerKeyMissingFromTheOptionsHasNoCandidate()
    {
        $built = CorrectInputBuilder::forPart('choices', ['answer' => 5, 'questions' => ['a', 'b']], false, 0, 27, [1, 0], '');
        $this->assertSame(['candidates' => [], 'skip' => null], $built);
    }

    public function testMultansChecksTheDisplayPositionsOfEveryAnswerKey()
    {
        $vars = ['answers' => '0,2', 'questions' => ['a', 'b', 'c']];
        $this->assertSame(['qn27' => [0 => 0, 2 => 2]], $this->first('multans', $vars, [2, 1, 0]));
    }

    public function testMultansEmptyAnswersChecksTheAppendedNoneOfThese()
    {
        $vars = ['answers' => '', 'questions' => ['a', 'b', 'c']];
        $this->assertSame(['qn27' => [1 => 1]], $this->first('multans', $vars, [0, 3, 1, 2]));
    }

    public function testMatchingPicksTheAnswerShownForEachQuestion()
    {
        $vars = ['questions' => ['q0', 'q1'], 'answers' => ['a0', 'a1']];
        $this->assertSame(['qn27-0' => '0', 'qn27-1' => '1'], $this->first('matching', $vars, [[1, 0], [1, 0]]));
    }

    public function testMatchingFollowsMatchlist()
    {
        $vars = ['questions' => ['q0', 'q1'], 'answers' => ['a0', 'a1'], 'matchlist' => '1,0'];
        $this->assertSame(['qn27-0' => '1', 'qn27-1' => '0'], $this->first('matching', $vars, [[0, 1], [0, 1]]));
    }

    public function testSizedMatrixIsTypedCellByCell()
    {
        $vars = ['answer' => '[(1,2),(3,4)]', 'answersize' => '2,2'];
        $this->assertSame(['qn27-0' => '1', 'qn27-1' => '2', 'qn27-2' => '3', 'qn27-3' => '4'], $this->first('matrix', $vars));
    }

    public function testUnsizedMatrixIsOneEntry()
    {
        $this->assertSame(['qn27' => '[(1,2),(3,4)]'], $this->first('calcmatrix', ['answer' => '[(1,2),(3,4)]']));
    }

    public function testCalculatedFractionFormatGetsAFraction()
    {
        $this->assertSame(['qn27' => '1/2'], $this->first('calculated', ['answer' => 0.5, 'answerformat' => 'reducedfraction']));
    }

    public function testNumberRangeGetsAnInteriorPoint()
    {
        $this->assertSame(['qn27' => '3.5'], $this->first('number', ['answer' => '[2,5)']));
    }

    public function testNumberOrAlternativesAreEachACandidate()
    {
        $built = CorrectInputBuilder::forPart('number', ['answer' => '3 or 5'], false, 0, 27, null, '');
        $this->assertSame([['qn27' => '3'], ['qn27' => '5']], $built['candidates']);
    }

    public function testIntervalInequalityFormatIsTypedAsAnInequality()
    {
        $vars = ['answer' => '(-oo,2)U[3,5)', 'answerformat' => 'inequality', 'variables' => 't'];
        $this->assertSame(['qn27' => 't<2 or 3 <= t < 5'], $this->first('calcinterval', $vars));
    }

    public function testIntervalInequalityAllButOnePoint()
    {
        $vars = ['answer' => '(-oo,2)U(2,oo)', 'answerformat' => 'inequality'];
        $this->assertSame(['qn27' => 'x<2 or x>2'], $this->first('interval', $vars));
    }

    public function testMultipartReadsThePartValueAndPartKey()
    {
        $vars = ['answer' => [0 => 3, 1 => 'x^2']];
        $this->assertSame(['qn28001' => 'x^2'], $this->first('numfunc', $vars, null, '', true, 1, 28001));
    }

    public function testShownAnswerIsTheLastCandidate()
    {
        $built = CorrectInputBuilder::forPart('numfunc', ['answer' => '3x'], false, 0, 27, null, '`3 x`');
        $this->assertSame([['qn27' => '3x'], ['qn27' => '3 x']], $built['candidates']);
    }

    public function testManuallyGradedAndDrawnAnswersAreSkipped()
    {
        foreach (['draw', 'file', 'conditional'] as $anstype) {
            $built = CorrectInputBuilder::forPart($anstype, [], false, 0, 27, null, '');
            $this->assertNotNull($built['skip'], $anstype);
        }
        $this->assertNotNull(CorrectInputBuilder::forPart('essay', [], false, 0, 27, null, '')['skip']);
        $this->assertSame([['qn27' => 'self-test answer']],
            CorrectInputBuilder::forPart('essay', ['scoremethod' => 'takeanything'], false, 0, 27, null, '')['candidates']);
    }
}
