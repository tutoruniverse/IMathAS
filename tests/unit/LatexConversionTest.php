<?php

require_once __DIR__ . '/../../filter/math/ASCIIMath2TeX.php';
require_once __DIR__ . '/../../filter/math/latexconversion.php';

use PHPUnit\Framework\TestCase;

/**
 * applyLatexConversion() feeds the print path (problems.php with convert_to_latex). Its TeX ends up in
 * pandoc, which refuses a bare "&" outside a matrix.
 */
final class LatexConversionTest extends TestCase
{
    private $AMT;

    protected function setUp(): void
    {
        $this->AMT = new AMtoTeX;
    }

    public function testNamedEntityInChoiceBecomesItsCharacter()
    {
        $html = '<label for="qn28000-0">`&ne;0`</label>';

        $this->assertSame(
            '<label for="qn28000-0"><math-field read-only="">≠0</math-field></label>',
            applyLatexConversion($html, $this->AMT)
        );
    }

    public function testNamedEntityInAnswerMode()
    {
        $this->assertSame('\[≤x\]', applyLatexConversion('`&le;x`', $this->AMT, false, true));
    }

    public function testLessThanStillConvertsToLt()
    {
        $this->assertSame(
            '<math-field read-only="">x\lt y</math-field>',
            applyLatexConversion('`x&lt;y`', $this->AMT)
        );
    }

    public function testConvertsEveryStringInAnArray()
    {
        $this->assertSame(
            ['a' => '<math-field read-only="">≠0</math-field>', 'b' => 3],
            applyLatexConversion(['a' => '`&ne;0`', 'b' => 3], $this->AMT)
        );
    }
}
