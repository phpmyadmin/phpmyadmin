<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Common
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Common;
use PhpMyAdmin\Theme;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * This class is for testing PhpMyAdmin\Server\Common methods
 *
 * @package PhpMyAdmin-test
 */
class CommonTest extends TestCase
{
    /**
     * Test for Common::getHtmlForSubPageHeader
     *
     * @return void
     */
    public function testPMAGetSubPageHeader()
    {
        //server_engines
        $html = Common::getHtmlForSubPageHeader("engines");
        $this->assertContains(
            '<img src="themes/dot.gif" title="" alt="" class="icon ic_b_engine" />',
            $html
        );
        $this->assertContains(
            'Storage Engines',
            $html
        );

        //server_databases
        $html = Common::getHtmlForSubPageHeader("databases");
        $this->assertContains(
            '<img src="themes/dot.gif" title="" alt="" class="icon ic_s_db" />',
            $html
        );
        $this->assertContains(
            'Databases',
            $html
        );

        //server_replication
        $html = Common::getHtmlForSubPageHeader("replication");
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
