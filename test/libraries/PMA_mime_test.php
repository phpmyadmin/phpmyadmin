<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for mime.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/mime.lib.php';

/**
 * Test for mime detection.
 *
 * @package PhpMyAdmin-test
 */
class PMA_MIME_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_detectMIME
     *
     * @param string $test   MIME to test
     * @param string $output Expected output
     *
     * @return void
     * @dataProvider providerForTestDetectMIME
     */
    public function testDetectMIME($test, $output)
    {

        $this->assertEquals(
            PMA_detectMIME($test),
            $output
        );
    }

    /**
     * Provider for testPMA_detectMIME
     *
     * @return array data for testPMA_detectMIME
     */
    public function providerForTestDetectMIME()
    {
        return array(
            array(
                'pma',
                'application/octet-stream'
            ),
            array(
                'GIF',
                'image/gif'
            ),
            array(
                "\x89PNG",
                'image/png'
            ),
            array(
                chr(0xff) . chr(0xd8),
                'image/jpeg'
            ),
        );
    }
}
