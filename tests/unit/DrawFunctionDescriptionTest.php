<?php

require_once __DIR__ . '/../../assessment/mathparser.php';
require_once __DIR__ . '/../../assess2/questions/scorepart/convert_ans_to_str.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for draw function description conversion (fans_* functions).
 *
 * Uses identity coordinate transforms (pixtox/pixtoy = identity) so that
 * pixel values equal math values, making expected outputs easy to compute.
 *
 * POST key format by qtype:
 *   - qtype=multipart : qn{qn*1000 + part_number}  (e.g. qn28000 for qn=28, part 0)
 *   - qtype=draw      : qn{qn}                      (e.g. qn27 for qn=27)
 */
final class DrawFunctionDescriptionTest extends TestCase
{
    /** Identity pixel-to-coordinate transforms */
    private $pixtox;
    private $pixtoy;

    protected function setUp(): void
    {
        $this->pixtox = function ($px) { return floatval($px); };
        $this->pixtoy = function ($py) { return floatval($py); };
    }

    // -------------------------------------------------------------------------
    // fmt() helper
    // -------------------------------------------------------------------------

    public function testFmtRemovesTrailingZeros()
    {
        $this->assertEquals('4', fmt(4.0));
    }

    public function testFmtKeepsSignificantDecimals()
    {
        $this->assertEquals('-0.0625', fmt(-0.0625));
    }

    public function testFmtHandlesZero()
    {
        $this->assertEquals('0', fmt(0));
    }

    public function testFmtHandlesInteger()
    {
        $this->assertEquals('3', fmt(3));
    }

    public function testFmtHandlesDecimal()
    {
        $this->assertEquals('2.5', fmt(2.5));
    }

    // -------------------------------------------------------------------------
    // fans_lines — type 5 (line)
    // -------------------------------------------------------------------------

    public function testFansLinesBasic()
    {
        // slope=2, intercept=0: trivial intercept dropped, coefficient kept
        $result = fans_lines(0, 0, 2, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line: y = 2 * x", $result[0]);
    }

    public function testFansLinesWithIntercept()
    {
        // slope=1, intercept=2: trivial coefficient dropped
        $result = fans_lines(0, 2, 3, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line: y = x + 2", $result[0]);
    }

    public function testFansLinesNonTrivial()
    {
        // slope=3, intercept=2: both non-trivial, no simplification
        $result = fans_lines(0, 2, 1, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line: y = 3 * x + 2", $result[0]);
    }

    public function testFansLinesNegativeSlope()
    {
        // From (0,4) to (2,0): slope=-2, intercept=4
        $result = fans_lines(0, 4, 2, 0, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line: y = -2 * x + 4", $result[0]);
    }

    public function testFansLinesNegativeSlopeAndIntercept()
    {
        // From (-3,5) to (2,-5): slope=(−5−5)/(2−(−3))=−2, intercept=5−(−2)(−3)=−1
        $result = fans_lines(-3, 5, 2, -5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line: y = -2 * x - 1", $result[0]);
    }

    public function testFansLinesZeroSlope()
    {
        // Horizontal line through (0, 8): slope=0, intercept=8 → y = 8
        $result = fans_lines(-2, 8, 4, 8, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line: y = 8", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_vecs — types 5.2 (ray), 5.3 (segment), 5.4 (vector)
    // -------------------------------------------------------------------------

    public function testFansVecsRay()
    {
        // slope=1, intercept=0: y = x; range starts at x=0
        $result = fans_vecs(0, 0, 2, 2, 'r', $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a ray: y = x from x = 0, ", $result[0]);
    }

    public function testFansVecsSegment()
    {
        // slope=1, intercept=0: y = x; segment from x=0 to x=4
        $result = fans_vecs(0, 0, 4, 4, 'ls', $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a line segment: y = x from x = 0 -> 4, ", $result[0]);
    }

    public function testFansVecsVector()
    {
        // slope=1, intercept=0: y = x; vector from x=1 to x=3
        $result = fans_vecs(1, 1, 3, 3, 'v', $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a vector: y = x from x = 1 -> 3, ", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_parabs — type 6 (vertical parabola)
    // -------------------------------------------------------------------------

    public function testFansParabsVertexAtOrigin()
    {
        // vertex (0,0), passes through (2,4): a = (4-0)/(2-0)^2 = 1
        $result = fans_parabs(0, 0, 2, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a parabola: y = x^2", $result[0]);
    }

    public function testFansParabsWithOffset()
    {
        // vertex (-1, 2), passes through (1, 6): a = (6-2)/(1-(-1))^2 = 4/4 = 1
        $result = fans_parabs(-1, 2, 1, 6, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a parabola: y = (x + 1)^2 + 2", $result[0]);
    }

    public function testFansParabsPositiveAWithOffset()
    {
        // vertex (3,-2), through (5,6): a=(6−(−2))/(5−3)²=8/4=2
        $result = fans_parabs(3, -2, 5, 6, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a parabola: y = 2 * (x - 3)^2 - 2", $result[0]);
    }

    public function testFansParabsNegativeA()
    {
        // vertex (-2,1), through (1,-8): a=(−8−1)/(1−(−2))²=−9/9=−1
        $result = fans_parabs(-2, 1, 1, -8, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a parabola: y = -(x + 2)^2 + 1", $result[0]);
    }

    public function testFansParabsFractionalA()
    {
        // vertex (5,4), through (7,5): a=(5-4)/(7-5)^2=0.25
        $result = fans_parabs(5, 4, 7, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a parabola: y = 0.25 * (x - 5)^2 + 4", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_hparabs — type 6.1 (horizontal parabola)
    // -------------------------------------------------------------------------

    public function testFansHparabsBasic()
    {
        // vertex (0,0), passes through (4,2): x = a*(y-k)^2 + h, a=(4-0)/(2-0)^2=1
        $result = fans_hparabs(0, 0, 4, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a horizontal parabola: x = y^2", $result[0]);
    }

    public function testFansHparabsNonIntegerA()
    {
        // vertex (1,-3), through (5,1): a=(5−1)/(1−(−3))²=4/16=0.25
        $result = fans_hparabs(1, -3, 5, 1, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a horizontal parabola: x = 0.25 * (y + 3)^2 + 1", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_sqrts — type 6.5 (square root)
    // -------------------------------------------------------------------------

    public function testFansSqrtsBasic()
    {
        // vertex (0,0), ref (4,2): flip=1, a=(2-0)/sqrt(4-0)=1
        $result = fans_sqrts(0, 0, 4, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a square root function: y = sqrt(x)", $result[0]);
    }

    public function testFansSqrtsWithOffsetAndScaling()
    {
        // vertex (-1,2), through (3,6): a=(6−2)/sqrt(3−(−1))=4/sqrt(4)=2
        $result = fans_sqrts(-1, 2, 3, 6, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a square root function: y = 2 * sqrt(x + 1) + 2", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_cubics — type 6.3 (cubic)
    // -------------------------------------------------------------------------

    public function testFansCubicsBasic()
    {
        // vertex (0,0), ref (1,1): a=cbrt(1)/1=1; trivial coefficient and offset dropped
        $result = fans_cubics(0, 0, 1, 1, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cubic function: y = x^3", $result[0]);
    }

    public function testFansCubicsNonUnitA()
    {
        // vertex (1,2), through (2,10): a=cbrt(10−2)/(2−1)=cbrt(8)/1=2
        $result = fans_cubics(1, 2, 2, 10, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cubic function: y = (2 * (x - 1))^3 + 2", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_cuberoots — type 6.6 (cube root)
    // -------------------------------------------------------------------------

    public function testFansCuberootsBasic()
    {
        // vertex (0,0), ref (1,1): a=(1^3)/1=1; trivial coefficient and offset dropped
        $result = fans_cuberoots(0, 0, 1, 1, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cube root function: x = y^3", $result[0]);
    }

    public function testFansCuberootsWithOffset()
    {
        // vertex (-1,2), through (7,4): a=(2³)/8=1; trivial coefficient dropped
        $result = fans_cuberoots(-1, 2, 7, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cube root function: x = (y - 2)^3 - 1", $result[0]);
    }

    public function testFansCuberootsNonUnitA()
    {
        // vertex (0,0), through (1,2): a=(2³)/1=8; shows /8 form
        $result = fans_cuberoots(0, 0, 1, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cube root function: x = y^3 / 8", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_circs — type 7 (circle)
    // -------------------------------------------------------------------------

    public function testFansCircsRadiusThree()
    {
        // center (0,0), point on circle (3,0): r^2 = 9
        $result = fans_circs(0, 0, 3, 0, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a circle: x^2 + y^2 = 9", $result[0]);
    }

    public function testFansCircsWithCenter()
    {
        // center (1,2), point on circle (4,2): r^2=9
        $result = fans_circs(1, 2, 4, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a circle: (x - 1)^2 + (y - 2)^2 = 9", $result[0]);
    }

    public function testFansCircsNonOriginCenter()
    {
        // center (1,-2), through (4,2): r²=(4−1)²+(2−(−2))²=9+16=25
        $result = fans_circs(1, -2, 4, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a circle: (x - 1)^2 + (y + 2)^2 = 25", $result[0]);
    }


    // -------------------------------------------------------------------------
    // fans_ellipses — type 7.2 (ellipse)
    // -------------------------------------------------------------------------

    public function testFansEllipsesBasic()
    {
        // center (0,0), semi-axes a=3 (x), b=2 (y)
        $result = fans_ellipses(0, 0, 3, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes an ellipse: x^2 / 9 + y^2 / 4 = 1",
            $result[0]
        );
    }

    public function testFansEllipsesOffCenter()
    {
        // center (-1,2), semi-axes 4 (x), 3 (y): call with ref (3,5)
        // ma=|3−(−1)|=4, mb=|5−2|=3; vertex on ellipse: (3,2) → ((1/4)*4)²=1 ✓
        $result = fans_ellipses(-1, 2, 3, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes an ellipse: (x + 1)^2 / 16 + (y - 2)^2 / 9 = 1",
            $result[0]
        );
    }

    // -------------------------------------------------------------------------
    // fans_vhyperbolas — type 7.4 (vertical hyperbola)
    // -------------------------------------------------------------------------

    public function testFansVhyperbolasBasic()
    {
        // center (0,0), semi-axes a=3, b=2
        $result = fans_vhyperbolas(0, 0, 3, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes a vertical hyperbola: y^2 / 4 - x^2 / 9 = 1",
            $result[0]
        );
    }

    public function testFansVhyperbolasOffCenter()
    {
        // center (1,-2), ref (4,0): ma=|4−1|=3, mb=|0−(−2)|=2
        // vertex at (1,0): ((1/2)*(0+2))²−0=1 ✓
        $result = fans_vhyperbolas(1, -2, 4, 0, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes a vertical hyperbola: (y + 2)^2 / 4 - (x - 1)^2 / 9 = 1",
            $result[0]
        );
    }

    // -------------------------------------------------------------------------
    // fans_hhyperbolas — type 7.5 (horizontal hyperbola)
    // -------------------------------------------------------------------------

    public function testFansHhyperbolasBasic()
    {
        // center (0,0), semi-axes a=3, b=2
        $result = fans_hhyperbolas(0, 0, 3, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes a horizontal hyperbola: x^2 / 9 - y^2 / 4 = 1",
            $result[0]
        );
    }

    public function testFansHhyperbolasOffCenter()
    {
        // center (-2,1), ref (2,4): ma=|2−(−2)|=4, mb=|4−1|=3
        // vertex at (2,1): ((1/4)*(2+2))²−0=1 ✓
        $result = fans_hhyperbolas(-2, 1, 2, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes a horizontal hyperbola: (x + 2)^2 / 16 - (y - 1)^2 / 9 = 1",
            $result[0]
        );
    }

    // -------------------------------------------------------------------------
    // fans_abs — type 8 (absolute value)
    // -------------------------------------------------------------------------

    public function testFansAbsBasic()
    {
        // vertex (0,0), ref (2,2): a=1
        $result = fans_abs(0, 0, 2, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes an absolute value function: y = abs(x)", $result[0]);
    }

    public function testFansAbsNegativeSlope()
    {
        // vertex (2,4), ref (0,2): a=(2-4)/(0-2)=1, mh(2)>mx(0) → flip → a=-1
        $result = fans_abs(2, 4, 0, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes an absolute value function: y = -abs(x - 2) + 4", $result[0]);
    }

    public function testFansAbsNonOriginVertex()
    {
        // vertex (-3,1), through (1,5): a=(5−1)/(1−(−3))=1, mh<mx no flip
        $result = fans_abs(-3, 1, 1, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes an absolute value function: y = abs(x + 3) + 1", $result[0]);
    }

    public function testFansAbsFractionalSlope()
    {
        // vertex (2,3), through (6,5): a=(5−3)/(6−2)=0.5, mh<mx no flip
        $result = fans_abs(2, 3, 6, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes an absolute value function: y = 0.5 * abs(x - 2) + 3", $result[0]);
    }

    public function testFansAbsNegativeA()
    {
        // vertex (1,4), through (-1,2): a=(2−4)/(−1−1)=1, mh(1)>mx(−1) → flip → a=−1
        $result = fans_abs(1, 4, -1, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes an absolute value function: y = -abs(x - 1) + 4", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_rats — type 8.2 (rational)
    // -------------------------------------------------------------------------

    public function testFansRatsBasic()
    {
        // asymptotes (0,0), ref (2,3): ma=6; zero asymptotes simplify to y = 6 / x
        $result = fans_rats(0, 0, 2, 3, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a rational function: y = 6 / x", $result[0]);
    }

    public function testFansRatsOffCenterAsymptotes()
    {
        // asymptotes x=2,y=3; through (4,7): ma=8; standard form fraction + offset
        $result = fans_rats(2, 3, 4, 7, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a rational function: y = 8 / (x - 2) + 3", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_exps — types 8.3 / 8.5 (exponential)
    // -------------------------------------------------------------------------

    public function testFansExpsUnitCoefficient()
    {
        // y = 2^x: horizasy=0, through (0,1) and (1,2), xop=0
        // str=1 after computation → coefficient omitted; y = 2^(x)
        $result = fans_exps(0, 1, 1, 2, 0, 0, 8.3, 0, 0, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes an exponential function: y = 2^(x)", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_coss — type 9 (cosine)
    // -------------------------------------------------------------------------

    public function testFansCossBasic()
    {
        // max (0,2), min (pi,−2): amp=2, b=1, mc=0, md=0; all trivial terms dropped
        $result = fans_coss(0, 2, M_PI, -2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cosine function: y = 2 * cos(x)", $result[0]);
    }

    public function testFansCossWithVerticalShiftAndPhase()
    {
        // max (2,3), min (2+pi,−1): amp=2, b=1, md=1, mc=2
        // trivial b=1 dropped inside cos; standard A*cos(...)+D form
        $result = fans_coss(2, 3, 2 + M_PI, -1, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cosine function: y = 2 * cos(x - 2) + 1", $result[0]);
    }

    public function testFansCossNonUnitFrequency()
    {
        // max (0,3), min (2,−1): amp=2, b=pi/2≈1.570796, md=1, mc=0
        // non-trivial b shows full coefficient; trivial mc=0 and md in kterm
        $result = fans_coss(0, 3, 2, -1, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a cosine function: y = 2 * cos(1.570796 * x) + 1", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_sins — type 9.1 (sine)
    // -------------------------------------------------------------------------

    public function testFansSinsBasic()
    {
        // Zero crossing at (0,0), peak at (1,2): amp=2, B=π/2≈1.570796, md=0
        // y = 2 * sin(1.570796 * x)
        $result = fans_sins(0, 0, 1, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a sine function: y = 2 * sin(1.570796 * x)", $result[0]);
    }

    public function testFansSinsNegativeAmplitude()
    {
        // Zero crossing at (0,0), trough at (1,-3): amp=-3, B=π/2≈1.570796
        // y = -3 * sin(1.570796 * x)
        $result = fans_sins(0, 0, 1, -3, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a sine function: y = -3 * sin(1.570796 * x)", $result[0]);
    }

    public function testFansSinsWithOffset()
    {
        // Zero crossing at (-1, 3), peak at (1, 5): amp=2, B=π/4≈0.785398, md=3
        // y = 2 * sin(0.785398 * (x + 1)) + 3
        $result = fans_sins(-1, 3, 1, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a sine function: y = 2 * sin(0.785398 * (x + 1)) + 3", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_tan — type 9.2 (tangent)
    // -------------------------------------------------------------------------

    public function testFansTanBasic()
    {
        // center (0,0), ref (1,1): amp=1, b=pi/4≈0.785398, mh=0, mk=0
        // trivial amp=1 and offset=0 dropped; trivial mh=0 simplifies arg to just b*x
        $result = fans_tan(0, 0, 1, 1, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a tangent function: y = tan(0.785398 * x)", $result[0]);
    }

    public function testFansTanWithOffsetCenterAndAmplitude()
    {
        // center (-1,2), ref (0,4): amp=4−2=2, b=pi/(4*1)=pi/4
        // verify: y(0)=2*tan(pi/4*(0+1))+2=2*1+2=4 ✓
        $result = fans_tan(-1, 2, 0, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals(
            "This includes a tangent function: y = 2 * tan(0.785398 * (x + 1)) + 2",
            $result[0]
        );
    }

    public function testFansTanUnitAmplitudeWithOffset()
    {
        // center (2,3), ref (3,4): amp=1, b=pi/4≈0.785398; trivial amp=1 dropped
        $result = fans_tan(2, 3, 3, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals("This includes a tangent function: y = tan(0.785398 * (x - 2)) + 3", $result[0]);
    }

    // -------------------------------------------------------------------------
    // fans_dots / fans_odots
    // -------------------------------------------------------------------------

    public function testFansDots()
    {
        $result = fans_dots(3, 4, $this->pixtox, $this->pixtoy);
        $this->assertEquals("There is a dot at: (3, 4)", $result[0]);
    }

    public function testFansOdots()
    {
        $result = fans_odots(-1, 2, $this->pixtox, $this->pixtoy);
        $this->assertEquals("There is an open dot at: (-1, 2)", $result[0]);
    }


    // -------------------------------------------------------------------------
    // fans_dots1d / fans_odots1d / fans_line1d
    // -------------------------------------------------------------------------

    public function testFansDots1d()
    {
        $result = fans_dots1d(5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("There is a dot at: 5", $result[0]);
    }

    public function testFansOdots1d()
    {
        $result = fans_odots1d(-3, $this->pixtox, $this->pixtoy);
        $this->assertEquals("There is an open dot at: -3", $result[0]);
    }

    public function testFansLine1d()
    {
        $result = fans_line1d(2, 5, $this->pixtox, $this->pixtoy);
        $this->assertEquals("There is a line from: 2 to 5", $result[0]);
    }

    // -------------------------------------------------------------------------
    // qtype POST key format documentation tests
    //
    // These verify the expected POST key names used by each qtype.
    // The multipart format uses qn{qn*1000+pn}; the draw format uses qn{qn}.
    // This logic lives in ScoreEngine.php::processStudentAnswers (multipart)
    // and processStudentAnswersNonMultipart (draw).
    // -------------------------------------------------------------------------

    public function testMultipartPostKeyFormat()
    {
        // For qtype=multipart, question 28 part 0 → key "qn28000"
        $qn = 28;
        $pn = 0;
        $expected_key = "qn" . ($qn * 1000 + $pn);
        $this->assertEquals("qn28000", $expected_key);
    }

    public function testMultipartPostKeyFormatPart1()
    {
        // For qtype=multipart, question 28 part 1 → key "qn28001"
        $qn = 28;
        $pn = 1;
        $expected_key = "qn" . ($qn * 1000 + $pn);
        $this->assertEquals("qn28001", $expected_key);
    }

    public function testDrawPostKeyFormat()
    {
        // For qtype=draw (non-multipart), question 27 → key "qn27"
        $qn = 27;
        $expected_key = "qn" . $qn;
        $this->assertEquals("qn27", $expected_key);
    }
}
