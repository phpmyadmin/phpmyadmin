<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_common.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;


require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_common.lib.php';


/**
 * PMA_ServerCommon_Test class
 *
 * this class is for testing server_common.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerCommon_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
    }

    /**
     * Test for PMA_getHtmlForSubPageHeader
     *
     * @return void
     */
    public function testPMAGetSubPageHeader()
    {
        //server_engines
        $html = PMA_getHtmlForSubPageHeader("engines");
        $this->assertContains(
            '<img src="themes/dot.gif" title="" alt="" class="icon ic_b_engine" />',
            $html
        );
        $this->assertContains(
            'Storage Engines',
            $html
        );

        //server_databases
        $html = PMA_getHtmlForSubPageHeader("databases");
        $this->assertContains(
            '<img src="themes/dot.gif" title="" alt="" class="icon ic_s_db" />',
            $html
        );
        $this->assertContains(
            'Databases',
            $html
        );

        //server_replication
        $html = PMA_getHtmlForSubPageHeader("replication");
        $replication_img = '<img src="themes/dot.gif" title="" '
            . 'alt="" class="icon ic_s_replication" />';
        $this->assertContains(
            $replication_img,
            $html
        );
        $this->assertContains(
            'Replication',
            $html
        );
    }

}
