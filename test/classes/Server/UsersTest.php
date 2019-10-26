<?php
/**
 * Tests for PhpMyAdmin\Server\Users
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Users;
use PhpMyAdmin\Url;
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
        $html = Users::getHtmlForSubMenusOnUsersPage(Url::getFromRoute('/server/privileges'));

        //validate 1: topmenu2
        $this->assertStringContainsString(
            '<ul id="topmenu2">',
            $html
        );

        //validate 2: tabactive for /server/privileges
        $this->assertStringContainsString(
            '<a class="tabactive" href="' . Url::getFromRoute('/server/privileges'),
            $html
        );
        $this->assertStringContainsString(
            __('User accounts overview'),
            $html
        );

        //validate 3: not-active for /server/user_groups
        $this->assertStringContainsString(
            '<a href="index.php?route=/server/user_groups',
            $html
        );
        $this->assertStringContainsString(
            __('User groups'),
            $html
        );
    }
}
