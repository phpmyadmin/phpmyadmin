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
     * Test for PMA_aix_iconv_wrapper
     *
     * @param string $in_charset         Non IBM-AIX-Compliant in-charset
     * @param string $out_charset        Non IBM-AIX-Compliant out-charset
     * @param string $in_charset_mapped  IBM-AIX-Compliant in-charset
     * @param string $out_charset_mapped IBM-AIX-Compliant out-charset
     * @param string $str                String to test
     *
     * @return void
     * @dataProvider iconvDataProvider
     */
    public function testIconvWrapper($in_charset, $out_charset,
        $in_charset_mapped, $out_charset_mapped, $str
    ) {
        $this->assertEquals(
            iconv($in_charset_mapped, $out_charset_mapped, $str),
            PMA_aix_iconv_wrapper($in_charset, $out_charset, $str)
        );
    }


    /**
     * Data provider for testIconvWrapper
     *
     * @return array data for testIconvWrapper test case
     */
    public function iconvDataProvider()
    {
        return array(
            array(
                'UTF-8',
                'ISO-8859-1//IGNORE',
                'UTF-8',
                'ISO-8859-1//IGNORE',
                'Euro Symbol: €'
            ),
            array(
                'UTF-8',
                'ISO-8859-1//IGNORE//TRANSLIT',
                'UTF-8',
                'ISO-8859-1//IGNORE',
                'Euro Symbol: €'
            ),
            array('UTF-8',
                'ISO-8859-9',
                'UTF-8',
                'ISO8859-9',
                'Testing "string"'
            )
        );
    }
}
?>
