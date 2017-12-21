<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Server\Users
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Users;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\Server\UsersTest class
 *
 * This class is for testing PhpMyAdmin\Server\Users methods
 *
 * @package PhpMyAdmin-test
 */
class UsersTest extends TestCase
{
    /**
     * Test for Users::getHtmlForSubMenusOnUsersPage
     *
     * @return void
     */
    public function testGetHtmlForSubMenusOnUsersPage()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $html = Users::getHtmlForSubMenusOnUsersPage('server_privileges.php');

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
