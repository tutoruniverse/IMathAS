<?php

require_once __DIR__ . '/../../i18n/i18n.php';
require_once __DIR__ . '/../../includes/sanitize.php';
require_once __DIR__ . '/../../assess2/questions/answerboxhelpers.php';
require_once __DIR__ . '/../../assess2/questions/answerboxes/AnswerBoxParams.php';
require_once __DIR__ . '/../../assess2/questions/answerboxes/NTupleAnswerBox.php';
require_once __DIR__ . '/../../assess2/questions/answerboxes/ComplexNTupleAnswerBox.php';

use IMathAS\assess2\questions\answerboxes\AnswerBoxParams;
use IMathAS\assess2\questions\answerboxes\ComplexNTupleAnswerBox;
use IMathAS\assess2\questions\answerboxes\NTupleAnswerBox;
use PHPUnit\Framework\TestCase;

/**
 * Entry tip ("Requirements") shown under n-tuple answer boxes.
 * Each tip line is compared separately, as the student sees one line per <br/>.
 */
final class NTupleEntryTipTest extends TestCase
{
    const NUMBER_TUPLE = 'Enter your answer as an n-tuple of numbers. Example: (2,5.5172)';
    const POINT = 'Enter your answer as a point. Example: (2,5.5172)';
    const COMPLEX_TUPLE = 'Enter your answer as an n-tuple of complex numbers. Example: (2+3i,5.5)';
    const CALCULATION = 'Enter each value as a number (like 5, -3, 2.2172) or as a calculation (like 5/3, 2^3, 5+4)';
    const DNE = 'Enter DNE for Does Not Exist';
    const DNE_OR_INFINITY = 'Enter DNE for Does Not Exist, oo for Infinity';
    const NO_DECIMAL = 'Decimal values are not allowed';

    private function buildParams($anstype, $answerformat, $displayformat)
    {
        $params = new AnswerBoxParams();
        $params->setAnswerType($anstype);
        $params->setQuestionNumber(0);
        $params->setIsMultiPartQuestion(false);
        $params->setQuestionPartNumber(0);
        $params->setStudentLastAnswers('');
        $params->setIsConditional(false);
        $params->setColorboxKeyword('');
        $params->setQuestionWriterVars([
            'answerformat' => $answerformat,
            'displayformat' => $displayformat,
            'answer' => '(1,2)',
            'reqdecimals' => '',
        ]);
        return $params;
    }

    private function getTipLines($answerBox)
    {
        $answerBox->generate();
        return explode('<br/>', $answerBox->getEntryTip());
    }

    public function nTupleProvider()
    {
        return [
            'ntuple' => ['ntuple', '', '', [self::NUMBER_TUPLE, self::DNE]],
            'calcntuple' => ['calcntuple', '', '', [self::NUMBER_TUPLE, self::CALCULATION, self::DNE_OR_INFINITY]],
            'calcntuple, nodecimal' => ['calcntuple', 'nodecimal', '',
                [self::NUMBER_TUPLE, self::CALCULATION, self::DNE_OR_INFINITY, self::NO_DECIMAL]],
            'calcntuple, point display' => ['calcntuple', '', 'point',
                [self::POINT, self::CALCULATION, self::DNE_OR_INFINITY]],
        ];
    }

    /**
     * @dataProvider nTupleProvider
     */
    public function testNTupleEntryTip($anstype, $answerformat, $displayformat, $expectedLines)
    {
        $answerBox = new NTupleAnswerBox($this->buildParams($anstype, $answerformat, $displayformat));

        $this->assertSame($expectedLines, $this->getTipLines($answerBox));
    }

    public function complexNTupleProvider()
    {
        return [
            'complexntuple' => ['complexntuple', '', [self::COMPLEX_TUPLE, self::DNE]],
            'calccomplexntuple' => ['calccomplexntuple', '', [self::COMPLEX_TUPLE, self::CALCULATION, self::DNE_OR_INFINITY]],
            'calccomplexntuple, nodecimal' => ['calccomplexntuple', 'nodecimal',
                [self::COMPLEX_TUPLE, self::CALCULATION, self::DNE_OR_INFINITY, self::NO_DECIMAL]],
        ];
    }

    /**
     * @dataProvider complexNTupleProvider
     */
    public function testComplexNTupleEntryTip($anstype, $answerformat, $expectedLines)
    {
        $answerBox = new ComplexNTupleAnswerBox($this->buildParams($anstype, $answerformat, ''));

        $this->assertSame($expectedLines, $this->getTipLines($answerBox));
    }
}
