<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Charset Conversions for IBM AIX compliant codes
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/iconv_wrapper.lib.php';

/**
 * Tests for Charset Conversions for IBM AIX compliant codes
 *
 * @package PhpMyAdmin-test
 */
class PMA_Iconv_Wrapper_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Test for PMA_convertAIXMapCharsets
     *
     * @param string $in_charset         Non IBM-AIX-Compliant in-charset
     * @param string $out_charset        Non IBM-AIX-Compliant out-charset
     * @param string $in_charset_mapped  IBM-AIX-Compliant in-charset
     * @param string $out_charset_mapped IBM-AIX-Compliant out-charset
     *
     * @return void
     * @test
     * @dataProvider iconvDataProvider
     */
    public function testIconvMapCharsets($in_charset, $out_charset,
        $in_charset_mapped, $out_charset_mapped
    ) {
        $this->assertEquals(
            array($in_charset_mapped, $out_charset_mapped),
            PMA_convertAIXMapCharsets($in_charset, $out_charset)
        );
    }


    /**
     * Data provider for testIconvMapCharsets
     *
     * @return array data for testIconvMapCharsets test case
     */
    public function iconvDataProvider()
    {
        return array(
            array(
                'UTF-8',
                'ISO-8859-1//IGNORE',
                'UTF-8',
                'ISO-8859-1//IGNORE'
            ),
            array(
                'UTF-8',
                'ISO-8859-1//TRANSLIT',
                'UTF-8',
                'ISO8859-1'
            ),
            array('UTF-8',
                'ISO-8859-9',
                'UTF-8',
                'ISO8859-9'
            )
        );
    }
}
?>
