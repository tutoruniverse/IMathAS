<?php

$GLOBALS['allowedmacros'] = [];
require_once __DIR__ . '/../../assessment/macros/math.php';
require_once __DIR__ . '/../../assessment/macros/graph.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for addlabel(), addlabelabs(), adddrawcommand(), addfractionaxislabels(),
 * and mergeplots() graph injection functions.
 *
 * These functions inject draw commands into the embed tag produced by showplot()
 * via str_replace("' />"), which targets the closing quote of the last attribute.
 * showplot() keeps script= as the last attribute so injection always lands inside
 * the script value where the renderer will execute it.
 */
final class AddlabelTest extends TestCase
{
    /** showplot() output: function_list comes before script (script is last) */
    private $plot;

    protected function setUp()
    {
        $_SESSION['graphdisp'] = 1;

        $this->plot =
            "<embed type='image/svg+xml' align='middle' width='200' height='200'" .
            " function_list='[\"x^2\"]'" .
            " script='setBorder(5); initPicture(-5,5,-5,5);axes(1,1,1);'" .
            " />\n";
    }

    // -------------------------------------------------------------------------
    // addlabel
    // -------------------------------------------------------------------------

    public function testAddlabelInjectsIntoScriptAttribute()
    {
        $result = addlabel($this->plot, 1, 2, 'Hello');

        $this->assertRegExp(
            "/script='[^']*text\(\[1,2\],\"Hello\"\)/",
            $result,
            'text() command should be inside the script attribute'
        );
    }

    public function testAddlabelWithColorAndLocation()
    {
        $result = addlabel($this->plot, 0, 0, 'Origin', 'red', 'aboveleft');

        $this->assertRegExp(
            "/script='[^']*fontfill=\"red\";[^']*text\(\[0,0\],\"Origin\",\"aboveleft\"\)/",
            $result
        );
    }

    public function testAddlabelReturnsAltTextInScreenReaderMode()
    {
        $_SESSION['graphdisp'] = 0;

        $result = addlabel($this->plot, 1, 2, 'Hello');

        $this->assertContains('Label &quot;Hello&quot; at (1,2)', $result);
    }

    // -------------------------------------------------------------------------
    // addlabelabs
    // -------------------------------------------------------------------------

    public function testAddlabelabsInjectsIntoScriptAttribute()
    {
        $result = addlabelabs($this->plot, 10, 20, 'Abs');

        $this->assertRegExp(
            "/script='[^']*textabs\(\[10,20\],\"Abs\"\)/",
            $result,
            'textabs() command should be inside the script attribute'
        );
    }

    // -------------------------------------------------------------------------
    // adddrawcommand
    // -------------------------------------------------------------------------

    public function testAdddrawcommandInjectsIntoScriptAttribute()
    {
        $result = adddrawcommand($this->plot, 'circle([0,0],1);');

        $this->assertRegExp(
            "/script='[^']*circle\(\[0,0\],1\)/",
            $result,
            'draw command should be inside the script attribute'
        );
    }

    // -------------------------------------------------------------------------
    // addfractionaxislabels
    // -------------------------------------------------------------------------

    public function testAddfractionaxislabelsInjectsIntoScriptAttribute()
    {
        $result = addfractionaxislabels($this->plot, '1');

        $this->assertRegExp(
            "/script='[^']*text\(/",
            $result,
            'fraction axis label text() should be inside the script attribute'
        );
    }

    // -------------------------------------------------------------------------
    // mergeplots
    // -------------------------------------------------------------------------

    public function testMergeplotsInjectsCommandsIntoScriptAttribute()
    {
        $plotB =
            "<embed type='image/svg+xml' align='middle' width='200' height='200'" .
            " function_list='[\"circle\"]'" .
            " script='setBorder(5); initPicture(-5,5,-5,5);axes(1,1,1);circle([0,0],2);'" .
            " />\n";

        $result = mergeplots($this->plot, $plotB);

        $this->assertRegExp(
            "/script='[^']*circle\(\[0,0\],2\)/",
            $result,
            'merged draw command should be inside the script attribute'
        );
    }

    public function testMergeplotsDoesNotDuplicateInitPicture()
    {
        $plotB =
            "<embed type='image/svg+xml' align='middle' width='200' height='200'" .
            " function_list='[\"dot\"]'" .
            " script='setBorder(5); initPicture(-5,5,-5,5);axes(1,1,1);dot([1,1],\"open\");'" .
            " />\n";

        $result = mergeplots($this->plot, $plotB);

        $this->assertEquals(
            1,
            substr_count($result, 'initPicture'),
            'initPicture should not be duplicated after merge'
        );
    }
}
