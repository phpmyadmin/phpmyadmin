<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for server_users.lib.php
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/url_generating.lib.php';

/*
 * Include to test.
 */
require_once 'libraries/server_users.lib.php';

/**
 * PMA_ServerUsers_Test class
 *
 * This class is for testing server_users.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_ServerUsers_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_getHtmlForSubMenusOnUsersPage
     *
     * @return void
     */
    public function testPMAGetHtmlForSubMenusOnUsersPage()
    {
        $html = PMA_getHtmlForSubMenusOnUsersPage('server_privileges.php');

        //validate 1: topmenu2
        $this->assertContains(
            '<ul id="topmenu2">',
            $html
        );

        //validate 2: tabactive for server_privileges.php
        $this->assertContains(
            '<a class="tabactive" href="server_privileges.php',
            $html
        );
        $this->assertContains(
            __('User accounts overview'),
            $html
        );

        //validate 3: not-active for server_user_groups.php
        $this->assertContains(
            '<a href="server_user_groups.php',
            $html
        );
        $this->assertContains(
            __('User groups'),
            $html
        );
    }
}
