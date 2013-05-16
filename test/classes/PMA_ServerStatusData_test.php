<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ServerStatusData class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/ServerStatusData.class.php';

class PMA_ServerStatusData_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests for getMenuHtml
     *
     * @return void
     *
     */          
    public function testGetMenuHtml()
    {
        $ServerStatusData = new PMA_ServerStatusData();
        $html = $ServerStatusData->getMenuHtml();

        //server text and link
        $this->assertContains(
            'Server',
            $html
        );
        $this->assertContains(
            'server_status.php',
            $html
        );

        //server statistics text and link
        $this->assertContains(
            'Query statistics',
            $html
        );
        $this->assertContains(
            'server_status_queries.php',
            $html
        );

        //server variables text and link
        $this->assertContains(
            'All status variables',
            $html
        );
        $this->assertContains(
            'server_status_variables.php',
            $html
        );

        //server monitor text and link
        $this->assertContains(
            'Monitor',
            $html
        );
        $this->assertContains(
            'server_status_monitor.php',
            $html
        );

        //server Advisor text and link
        $this->assertContains(
            'Advisor',
            $html
        );
        $this->assertContains(
            'server_status_advisor.php',
            $html
        );
    }
}
?>
