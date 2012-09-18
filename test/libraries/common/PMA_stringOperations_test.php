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
require_once 'libraries/Util.class.php';

class PMA_stringOperations_test extends PHPUnit_Framework_TestCase
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
     */
    public function setUp()
    {
        global $GLOBALS, $_SESSION;
        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;

    }

    /**
     * data provider for PMA_Util::flipstring test
     *
     * @return array
     */
    public function flipStringDataProvider()
    {
        return array(
            array('test', "t<br />\ne<br />\ns<br />\nt"),
            array('te&nbsp;;st', "t<br />\ne<br />\n&nbsp;<br />\n;<br />\ns<br />\nt")
        );
    }

    /**
     * test of changing string from horizontal to vertical orientation
     * @dataProvider flipStringDataProvider
     */

    public function testFlipString($a, $e)
    {
        $this->assertEquals($e, PMA_Util::flipstring($a));
    }

    /**
     * data provider for PMA_Util::userDir test
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
     * @dataProvider userDirDataProvider
     */

    public function testUserDirString($a, $e)
    {
        $GLOBALS['cfg']['Server']['user'] = 'root';

        $this->assertEquals($e, PMA_Util::userDir($a));
    }

    /**
     * data provider for replace binary content test
     *
     * @return array
     */
    public function replaceBinaryContentsDataProvider()
    {
        return array(
            array("\x000", '\00'),
            array("\x08\x0a\x0d\x1atest", '\b\n\r\Ztest'),
            array("\ntest", '\ntest')
        );
    }

    /**
     * replace binary contents test
     * @dataProvider replaceBinaryContentsDataProvider
     */

    public function testReplaceBinaryContents($a, $e)
    {
        $this->assertEquals(
            $e, PMA_Util::replaceBinaryContents($a)
        );
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
     * @dataProvider duplicateFirstNewlineDataProvider
     */

    public function testDuplicateFirstNewline($a, $e)
    {
        $this->assertEquals(
            $e, PMA_Util::duplicateFirstNewline($a)
        );
    }

}
?>
