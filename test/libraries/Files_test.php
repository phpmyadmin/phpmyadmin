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


/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */
class FilesTest extends PHPUnit_Framework_TestCase
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

    public function testJsMessages()
    {
        ob_start();
        $GLOBALS['pmaThemeImage'] = '';
        $cfg = array(
            'AllowUserDropDatabase' => true,
            'GridEditing' => 'click',
        );
        require 'js/messages.php';
        $out = ob_get_contents();
        ob_end_clean();
        $this->assertContains('var PMA_messages = new Array();', $out);
    }

}
