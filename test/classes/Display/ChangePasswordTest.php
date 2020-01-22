<?php
/**
 * tests for PhpMyAdmin\Display\ChangePassword
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Config;
use PhpMyAdmin\Display\ChangePassword;
use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;
use function htmlspecialchars;

/**
 * ChangePasswordTest class
 *
 * this class is for testing PhpMyAdmin\Display\ChangePassword functions
 */
class ChangePasswordTest extends TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    protected function setUp(): void
    {
        //$GLOBALS
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = 'PMA_server';
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['PMA_PHP_SELF'] = '';
        $GLOBALS['server'] = 0;

        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = 'relation';
    }

    /**
     * Test for ChangePassword::getHtml
     *
     * @return void
     */
    public function testGetHtml()
    {
        $username = 'pma_username';
        $hostname = 'pma_hostname';
        $_REQUEST['route'] = '/server/privileges';

        //Call the test function
        $html = ChangePassword::getHtml('change_pw', $username, $hostname);

        $this->assertStringContainsString(
            Url::getFromRoute('/server/privileges'),
            $html
        );

        //Url::getHiddenInputs
        $this->assertStringContainsString(
            Url::getHiddenInputs(),
            $html
        );

        //$username & $hostname
        $this->assertStringContainsString(
            htmlspecialchars($username),
            $html
        );
        $this->assertStringContainsString(
            htmlspecialchars($hostname),
            $html
        );

        //labels
        $this->assertStringContainsString(
            __('Change password'),
            $html
        );
        $this->assertStringContainsString(
            __('No Password'),
            $html
        );
        $this->assertStringContainsString(
            __('Password:'),
            $html
        );
        $this->assertStringContainsString(
            __('Password:'),
            $html
        );
    }
}
