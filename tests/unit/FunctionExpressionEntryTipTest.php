<?php

$GLOBALS['allowedmacros'] = [];
require_once __DIR__ . '/../../i18n/i18n.php';
require_once __DIR__ . '/../../includes/sanitize.php';
require_once __DIR__ . '/../../assessment/macros/array.php';
require_once __DIR__ . '/../../assessment/macros/parsers.php';
require_once __DIR__ . '/../../assess2/questions/answerboxhelpers.php';
require_once __DIR__ . '/../../assess2/questions/answerboxes/AnswerBoxParams.php';
require_once __DIR__ . '/../../assess2/questions/answerboxes/FunctionExpressionAnswerBox.php';

use IMathAS\assess2\questions\answerboxes\AnswerBoxParams;
use IMathAS\assess2\questions\answerboxes\FunctionExpressionAnswerBox;
use PHPUnit\Framework\TestCase;

/**
 * Entry tip ("Requirements") shown under function/expression answer boxes.
 * Each tip line is compared separately, as the student sees one line per <br/>.
 */
final class FunctionExpressionEntryTipTest extends TestCase
{
    const EXPRESSION = 'Enter your answer as an expression. Example: 3x^2+1, x/5, (a+b)/c';
    const EQUATION = 'Enter your answer as an equation. Example: y=3x^2+1, 2+x+y=3';
    const MATCH_VARIABLES = 'Be sure your variables match those in the question';
    const COMPLEX_ALLOWED = 'Your answer can contain complex numbers.';

    private function getTipLines($answerformat)
    {
        $params = new AnswerBoxParams();
        $params->setAnswerType('numfunc');
        $params->setQuestionNumber(0);
        $params->setIsMultiPartQuestion(false);
        $params->setQuestionPartNumber(0);
        $params->setStudentLastAnswers('');
        $params->setIsConditional(false);
        $params->setColorboxKeyword('');
        $params->setQuestionWriterVars([
            'answerformat' => $answerformat,
            'variables' => 'x',
            'answer' => '',
        ]);

        $answerBox = new FunctionExpressionAnswerBox($params);
        $answerBox->generate();
        return explode('<br/>', $answerBox->getEntryTip());
    }

    public function entryTipProvider()
    {
        return [
            'expression' => ['', [self::EXPRESSION . "\n", self::MATCH_VARIABLES]],
            'equation' => ['equation', [self::EQUATION . "\n", self::MATCH_VARIABLES]],
            'expression, generalcomplex' => ['generalcomplex',
                [self::EXPRESSION . "\n", self::MATCH_VARIABLES, self::COMPLEX_ALLOWED]],
        ];
    }

    /**
     * @dataProvider entryTipProvider
     */
    public function testEntryTip($answerformat, $expectedLines)
    {
        $this->assertSame($expectedLines, $this->getTipLines($answerformat));
    }
}
