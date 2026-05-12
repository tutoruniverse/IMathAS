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

    /**
     * Modern showplot() emits embeds whose script attribute starts with
     * `axes(...)` directly — no `setBorder(...);` or `initPicture(...);`
     * prefix. mergeplots must still extract the additional draw commands
     * and inject them into plota's script, without leaking the second
     * embed tag as text.
     */
    public function testMergeplotsModernFormatInjectsCommands()
    {
        $plotA =
            "<embed type='image/svg+xml' align='middle' width='500' height='500'" .
            " function_list='[\"x^2\"]'" .
            " plot-func='abc'" .
            " script='axes(1,1,1,1,1);plot(\"x^2\");'" .
            " />\n";
        $plotB =
            "<embed type='image/svg+xml' align='middle' width='500' height='500'" .
            " function_list='[\"x^3\"]'" .
            " plot-func='def'" .
            " script='axes(1,1,1,1,1);plot(\"x^3\");'" .
            " />\n";

        $result = mergeplots($plotA, $plotB);

        $this->assertRegExp(
            "/script='[^']*plot\(\"x\^3\"\)/",
            $result,
            'plotB draw command should be inside plotA\'s script attribute'
        );
        $this->assertEquals(
            1,
            substr_count($result, '<embed'),
            'merged result must be a single embed tag — no nested embed leaked into the script'
        );
    }

    /**
     * ineqbetweenplot() injects fill/path commands into a modern showplot()
     * embed via str_replace("' />"). mergeplots() must merge such an embed
     * with another modern embed without spilling the second embed's script
     * payload as visible text below the graph.
     */
    public function testMergeplotsModernFormatWithIneqbetweenplotPayload()
    {
        $plotA =
            "<embed type='image/svg+xml' align='middle' width='500' height='500'" .
            " function_list='[]'" .
            " plot-func='abc'" .
            " script='axes(1,1,1,1,1);fill=\"transgreen\";strokewidth=0;path([[0,0],[1,0],[1,-3],[0,-3]]);'" .
            " />\n";
        $plotB =
            "<embed type='image/svg+xml' align='middle' width='500' height='500'" .
            " function_list='[\"line\"]'" .
            " plot-func='def'" .
            " script='axes(1,1,1,1,1);plot(\"x\");'" .
            " />\n";

        $result = mergeplots($plotA, $plotB);

        $this->assertEquals(
            1,
            substr_count($result, '<embed'),
            'merged result must be a single embed tag'
        );
        $this->assertRegExp(
            "/script='[^']*path\(\[\[0,0\]/",
            $result,
            'fill/path payload from ineqbetweenplot must remain in script'
        );
        $this->assertRegExp(
            "/script='[^']*plot\(\"x\"\)/",
            $result,
            'plotB plot() must be appended into plotA\'s script'
        );
    }

    /**
     * showplot() can emit a script whose payload begins with a stray empty
     * statement, e.g. `;  initPicture(...); axes(...);; stroke = "red";; plot(...)`.
     * The leading `;` must not block the setBorder/initPicture/axes strip;
     * otherwise the duplicate initPicture re-runs and clears the canvas.
     */
    public function testMergeplotsStripsBoilerplateWithLeadingSemicolon()
    {
        $plotA =
            "<embed type='image/svg+xml' align='middle' width='400' height='400'" .
            " script=';  initPicture(-2.5,2.5,-2.5,2.5); axes(1, 1, \"labels\", );;  stroke = \"blue\";;  plot(\"x\",0,1)' />\n";
        $plotB =
            "<embed type='image/svg+xml' align='middle' width='400' height='400'" .
            " script=';  initPicture(-2.5,2.5,-2.5,2.5); axes(1, 1, \"labels\", );;  stroke = \"red\";;  plot(\"x\",1,2)' />\n";

        $result = mergeplots($plotA, $plotB);

        $this->assertEquals(
            1,
            substr_count($result, 'initPicture'),
            'initPicture must not be duplicated when plotb starts with a stray semicolon'
        );
        $this->assertRegExp(
            "/stroke = \"red\";;\s*plot\(\"x\",1,2\)/",
            $result,
            'plotB draw commands must still be injected into plotA'
        );
        // plotA's script ends with `...plot("x",0,1)` (no trailing `;`). The
        // injected commands must be separated by `;` so they parse as a new
        // statement — otherwise we'd get `...plot("x",0,1)stroke = "red"...`.
        $this->assertNotRegExp(
            "/plot\(\"x\",0,1\)stroke/",
            $result,
            'separator semicolon between plotA tail and injected plotB commands must be preserved'
        );
    }
}
