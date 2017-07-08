<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for several string operations
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 * Test for several string operations
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_StringOperations_Test extends PHPUnit_Framework_TestCase
{

    /**
     * temporary variable for globals array
     */
    protected $tmpGlobals;

    /**
     * temporary variable for session array
     */
    protected $tmpSession;

    /**
     * storing globals and session
     *
     * @return void
     */
    public function setUp()
    {
        global $GLOBALS, $_SESSION;
        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;

    }

    /**
     * data provider for PhpMyAdmin\Util::userDir test
     *
     * @return array
     */
    public function userDirDataProvider()
    {
        return array(
            array('/var/pma_tmp/%u/', "/var/pma_tmp/root/"),
            array('/home/%u/pma', "/home/root/pma/")
        );
    }

    /**
     * test of generating user dir, globals are defined
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @return void
     *
     * @dataProvider userDirDataProvider
     */
    public function testUserDirString($a, $e)
    {
        $GLOBALS['cfg']['Server']['user'] = 'root';

        $this->assertEquals($e, PhpMyAdmin\Util::userDir($a));
    }

    /**
     * data provider for duplicate first newline test
     *
     * @return array
     */
    public function duplicateFirstNewlineDataProvider()
    {
        return array(
            array('test', 'test'),
            array("\r\ntest", "\n\r\ntest"),
            array("\ntest", "\ntest"),
            array("\n\r\ntest", "\n\r\ntest")
        );
    }

    /**
     * duplicate first newline test
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @return void
     *
     * @dataProvider duplicateFirstNewlineDataProvider
     */
    public function testDuplicateFirstNewline($a, $e)
    {
        $this->assertEquals(
            $e, PhpMyAdmin\Util::duplicateFirstNewline($a)
        );
    }

}
