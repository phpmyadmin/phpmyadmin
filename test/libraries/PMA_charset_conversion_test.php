<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Charset Conversions
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/charset_conversion.lib.php';
require_once 'libraries/iconv_wrapper.lib.php';

class PMA_Charset_Conversion_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_convertString
     *
     * @return void
     */
    public function testCharsetConversion()
    {
        $this->assertEquals(
            'test', 
            PMA_convertString('UTF-8', 'UTF-8', 'test')
        );
        
        $GLOBALS['PMA_recoding_engine'] = 6;
        $this->assertEquals(
            'test', 
            PMA_convertString('UTF-8', 'flat', 'test')
        );

        // TODO: remove function_exists if recode_string exists on server

        if (@function_exists('recode_string')) {
            $GLOBALS['PMA_recoding_engine'] = PMA_CHARSET_RECODE;
            $this->assertEquals(
                'Only That ecole & Can Be My Blame', 
                PMA_convertString('UTF-8', 'flat', 'Only That école & Can Be My Blame')
            );
        }

        $GLOBALS['PMA_recoding_engine'] = PMA_CHARSET_ICONV;
        $GLOBALS['cfg']['IconvExtraParams'] = '//TRANSLIT';
        $this->assertEquals(
            "This is the Euro symbol 'EUR'.", 
            PMA_convertString('UTF-8', 'ISO-8859-1', "This is the Euro symbol '€'.")
        );
        
        $GLOBALS['cfg']['IconvExtraParams'] = '//IGNORE';
        $GLOBALS['PMA_recoding_engine'] = PMA_CHARSET_ICONV_AIX;
        $this->assertEquals(
            "This is the Euro symbol ''.",
            PMA_convertString('UTF-8', 'ISO-8859-1', "This is the Euro symbol '€'.")
        );
        
    }
}
?>
