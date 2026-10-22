<?php

$GLOBALS['allowedmacros'] = [];
require_once __DIR__ . '/../../assessment/libs/plot3d.php';

use PHPUnit\Framework\TestCase;

/**
 * CalcPlot3D draws a constant-colour space curve WHITE when its colour is bare
 * hex (FF0000) but honours names, #hex and rgb(r,g,b), so the macro writes any
 * hex curve colour as rgb(). Vectors and points honour bare hex: left as given.
 */
final class CalcPlot3DprepFuncTest extends TestCase
{
    private function color($func)
    {
        preg_match('/;color=([^;]*)/', CalcPlot3DprepFunc($func), $m);
        return $m[1];
    }

    public function testHexCurveColourBecomesRgb()
    {
        $this->assertSame('rgb(255,0,0)', $this->color('curve,t,t,t,-1,1,50,2,FF0000'));
        $this->assertSame('rgb(0,0,255)', $this->color('curve,t,t,t,-1,1,50,2,0000ff'));
        $this->assertSame('rgb(255,0,0)', $this->color('curve,t,t,t,-1,1,50,2,#FF0000'));
        $this->assertSame('rgb(187,187,187)', $this->color('curve,t,t,t,-1,1,50,2,#bbb'));
        $this->assertSame('rgb(255,255,255)', $this->color('curve,t,t,t,-1,1,50,2,fff'));
    }

    public function testHexCurveKeepsConstcol()
    {
        $this->assertContains(';constcol=true;', CalcPlot3DprepFunc('curve,t,t,t,-1,1,50,2,FF0000'));
    }

    public function testNonHexCurveColoursUntouched()
    {
        $this->assertSame('red', $this->color('curve,t,t,t,-1,1,50,2,red'));
        $this->assertSame('rainbow', $this->color('curve,t,t,t,-1,1,50,2,rainbow'));
        $this->assertSame('gradient', $this->color('curve,t,t,t,-1,1,50,2,gradient'));
        $this->assertSame('rainbow', $this->color('curve,t,t,t'));
        $this->assertSame('FF00', $this->color('curve,t,t,t,-1,1,50,2,FF00'));
    }

    public function testVectorAndPointHexUntouched()
    {
        $this->assertSame('FF0000', $this->color('vector,1,2,3,FF0000'));
        $this->assertSame('0000FF', $this->color('point,1,2,3,0000FF,5'));
    }
}
