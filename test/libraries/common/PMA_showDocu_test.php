<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::showDocu from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;




/**
 ** Test for PMA\libraries\Util::showDocu from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_ShowDocu_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    function setup()
    {
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $GLOBALS['server'] = '99';
        $GLOBALS['cfg']['ServerDefault'] = 1;
    }

    /**
     * Test for showDocu
     *
     * @return void
     */
    function testShowDocu()
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fpage.html%23anchor" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help" /></a>',
            PMA\libraries\Util::showDocu('page', 'anchor')
        );

    }
}
