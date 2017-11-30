<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PHPUnit\Framework\TestCase;

/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */
class FilesTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    public function setUp()
    {
    }

    /**
     * Test for dynamic javascript files
     *
     * @param $name     string Filename to test
     * @param $expected string Expected output
     *
     * @return void
     *
     * @dataProvider listScripts
     */
    public function testDynamicJs($name, $expected)
    {
        $GLOBALS['pmaThemeImage'] = '';
        $GLOBALS['goto_whitelist'] = array('x');
        $_GET['scripts'] = '["ajax.js"]';
        $cfg = array(
            'AllowUserDropDatabase' => true,
            'GridEditing' => 'click',
            'OBGzip' => false,
            'ServerDefault' => 1,
        );
        $GLOBALS['cfg'] = $cfg;
        require $name;
        $buffer->stop();
        $out = $buffer->getContents();
        $this->assertContains($expected, $out);
    }

    /**
     * Data provider for scripts testing
     *
     * @return array
     */
    public function listScripts()
    {
        return array(
            array('js/whitelist.php', 'var PMA_gotoWhitelist'),
            array('js/messages.php', 'var PMA_messages = new Array();'),
        );
    }
}
