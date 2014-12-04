<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for gettext parsing.
 *
 * @package PhpMyAdmin-test
 */
require_once 'libraries/php-gettext/gettext.php';

class ParsingTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test for extract_plural_forms_header_from_po_header
     *
     * @return void
     */
    public function test_extract_plural_forms_header_from_po_header()
    {
        $parser = new gettext_reader(null);
        // It defaults to a "Western-style" plural header.
        $this->assertEquals(
            'nplurals=2; plural=n == 1 ? 0 : 1;',
            $parser->extract_plural_forms_header_from_po_header("")
        );

        // Extracting it from the middle of the header works.
        $this->assertEquals(
            'nplurals=1; plural=0;',
            $parser->extract_plural_forms_header_from_po_header(
                "Content-type: text/html; charset=UTF-8\n"
                . "Plural-Forms: nplurals=1; plural=0;\n"
                . "Last-Translator: nobody\n"
            )
        );

        // It's also case-insensitive.
        $this->assertEquals(
            'nplurals=1; plural=0;',
            $parser->extract_plural_forms_header_from_po_header(
                "PLURAL-forms: nplurals=1; plural=0;\n"
            )
        );

        // It falls back to default if it's not on a separate line.
        $this->assertEquals(
            'nplurals=2; plural=n == 1 ? 0 : 1;',
            $parser->extract_plural_forms_header_from_po_header(
                "Content-type: text/html; charset=UTF-8" // note the missing \n here
                . "Plural-Forms: nplurals=1; plural=0;\n"
                . "Last-Translator: nobody\n"
            )
        );
    }

    /**
     * Test for npgettext
     *
     * @param int    $number   Number
     * @param string $expected Expected output
     *
     * @return void
     *
     * @dataProvider data_provider_test_npgettext
     */
    public function test_npgettext($number, $expected)
    {
        $parser = new gettext_reader(null);
        $result = $parser->npgettext(
            "context",
            "%d pig went to the market\n",
            "%d pigs went to the market\n",
            $number
        );
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for test_npgettext
     *
     * @return array
     */
    public static function data_provider_test_npgettext()
    {
        return array(
            array(1, "%d pig went to the market\n"),
            array(2, "%d pigs went to the market\n"),
        );
    }

}

?>
