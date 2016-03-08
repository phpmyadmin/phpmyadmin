<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Charset Conversions
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\Encoding;
/*
 * Include to test.
 */
$cfg['RecodingEngine'] = null;

/**
 * Tests for Charset Conversions
 *
 * @package PhpMyAdmin-test
 */
class EncodingTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Encoding::initEngine();
    }

    public function tearDown()
    {
        Encoding::initEngine();
    }

    /**
     * Test for Encoding::convertString
     *
     * @return void
     * @test
     *
     * @group medium
     */
    public function testNoConversion()
    {
        $this->assertEquals(
            'test',
            Encoding::convertString('UTF-8', 'UTF-8', 'test')
        );
    }

    public function testInvalidConversion()
    {
        // Invalid value to use default case
        Encoding::setEngine(-1);
        $this->assertEquals(
            'test',
            Encoding::convertString('UTF-8', 'anything', 'test')
        );
    }

    public function testRecode()
    {
        if (! @function_exists('recode_string')) {
            $this->markTestSkipped('recode extension missing');
        }

        Encoding::setEngine(Encoding::ENGINE_RECODE);
        $this->assertEquals(
            'Only That ecole & Can Be My Blame',
            Encoding::convertString(
                'UTF-8', 'flat', 'Only That école & Can Be My Blame'
            )
        );
    }

    public function testIconv()
    {
        if (! @function_exists('iconv')) {
            $this->markTestSkipped('iconv extension missing');
        }

        $GLOBALS['cfg']['IconvExtraParams'] = '//TRANSLIT';
        Encoding::setEngine(Encoding::ENGINE_ICONV);
        $this->assertEquals(
            "This is the Euro symbol 'EUR'.",
            Encoding::convertString(
                'UTF-8', 'ISO-8859-1', "This is the Euro symbol '€'."
            )
        );
    }

    public function testMbstring()
    {
        if (! @function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('mbstring extension missing');
        }

        Encoding::setEngine(Encoding::ENGINE_MB);
        $this->assertEquals(
            "This is the Euro symbol '?'.",
            Encoding::convertString(
                'UTF-8', 'ISO-8859-1', "This is the Euro symbol '€'."
            )
        );
    }
}
