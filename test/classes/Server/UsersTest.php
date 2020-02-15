<?php
/**
 * Tests for PhpMyAdmin\Server\Users
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

        $this->assertStringContainsString(
            '<ul class="nav nav-pills m-2">',
            $html
        );

        $this->assertStringContainsString(
            '<a class="nav-link active" href="' . Url::getFromRoute('/server/privileges'),
            $html
        );
        $this->assertStringContainsString(
            __('User accounts overview'),
            $html
        );

        $this->assertStringContainsString(
            '<a class="nav-link" href="index.php?route=/server/user-groups',
            $html
        );
        $this->assertStringContainsString(
            __('User groups'),
            $html
        );
    }
}
