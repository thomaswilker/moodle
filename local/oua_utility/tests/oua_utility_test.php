<?php
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

/**
 * Test the php assertions actually work
 */
class oua_utility_testcase extends oua_advanced_testcase {
    function test_invalid_html_fails_assertion() {
        try {
            $invalidhtmlstringwithopentags = "<div><span>";
            $this->assertValidHtml($invalidhtmlstringwithopentags);
            self::fail("assertValidHtml should fail for html string: \n\n$invalidhtmlstringwithopentags");
        } catch (PHPUnit_Framework_ExpectationFailedException $e) { /* test passed */
        }

        try {
            $invalidhtmlstringwithwrongordertags = "<div><span></div></span>";
            $this->assertValidHtml($invalidhtmlstringwithwrongordertags);
            self::fail("assertValidHtml should fail for html string: \n\n$invalidhtmlstringwithwrongordertags");
        } catch (PHPUnit_Framework_ExpectationFailedException $e) { /* test passed */
        }

        try {
            $invalidhtmlstringwithnostarttag = "<span></div></span>";
            $this->assertValidHtml($invalidhtmlstringwithnostarttag);
            self::fail("assertValidHtml should fail for html string: \n\n$invalidhtmlstringwithnostarttag");
        } catch (PHPUnit_Framework_ExpectationFailedException $e) { /* test passed */
        }
    }

    function test_valid_html_passes_assertion() {
        $validhtml = "<div><span></span></div>";
        $this->assertValidHtml($validhtml);

        $validhtmlwithnamedentities = "<div>&quot; &nbsp; &amp;</div>";
        $this->assertValidHtml($validhtmlwithnamedentities);

        $validhtmlwithnamedentities = <<<HTMLDOC
<!DOCTYPE html>
<html>
<body>

<h1>My First Heading</h1>

<p>My first paragraph.</p>

</body>
</html>
HTMLDOC;
        $this->assertValidHtml($validhtmlwithnamedentities);
    }

    function test_domquery_asserts_correctly() {
        $htmldoc = <<<HTMLDOC
<!DOCTYPE html>
<html>
<body>
<div id="mydiv" class="active">
<h1>My First Heading</h1>

<p>My first paragraph.</p>
</div>
<div class="active">
</div>
</body>
</html>
HTMLDOC;
        $this->assertXpathDomQueryResultLengthEquals(1, "//div[contains(@id, 'mydiv') and contains(@class,'active')]", $htmldoc,
                                                     "Test should return 1 element");
        $this->assertXpathDomQueryResultLengthEquals(2, "//div[contains(@class,'active')]", $htmldoc,
                                                     "Test should return 2 elements");
        $this->assertXpathDomQueryResultLengthEquals(0, "//div[contains(@class,'blah')]", $htmldoc,
                                                     "Test should return 2 elements");
    }
}
