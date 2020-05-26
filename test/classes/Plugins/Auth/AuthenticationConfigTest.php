<?php
/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationConfig class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\Tests\AbstractTestCase;
use function ob_get_clean;
use function ob_start;

/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationConfig class
 */
class AuthenticationConfigTest extends AbstractTestCase
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['token_provided'] = true;
        $GLOBALS['token_mismatch'] = false;
        $this->object = new AuthenticationConfig();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::showLoginForm
     *
     * @return void
     */
    public function testAuth()
    {
        $this->assertTrue(
            $this->object->showLoginForm()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::readCredentials
     *
     * @return void
     */
    public function testAuthCheck()
    {
        $GLOBALS['cfg']['Server'] = [
            'user' => 'username',
            'password' => 'password',
        ];
        $this->assertTrue(
            $this->object->readCredentials()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::storeCredentials
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        $this->assertTrue(
            $this->object->storeCredentials()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::showFailure
     *
     * @return void
     */
    public function testAuthFails()
    {
        $GLOBALS['error_handler'] = new ErrorHandler();
        $GLOBALS['cfg']['Servers'] = [1];
        $GLOBALS['allowDeny_forbidden'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->object->showFailure('');
        $html = ob_get_clean();

        $this->assertStringContainsString(
            'You probably did not create a configuration file. You might want ' .
            'to use the <a href="setup/">setup script</a> to create one.',
            $html
        );

        $this->assertStringContainsString(
            '<strong>MySQL said: </strong><a href="./url.php?url=https%3A%2F%2F' .
            'dev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fserver-error-reference.html"' .
            ' target="mysql_doc">' .
            '<img src="themes/dot.gif" title="Documentation" alt="Documentation" ' .
            'class="icon ic_b_help"></a>',
            $html
        );

        $this->assertStringContainsString(
            'Cannot connect: invalid settings.',
            $html
        );

        $this->assertStringContainsString(
            '<a href="index.php?route=/&amp;server=0&amp;lang=en" '
            . 'class="button disableAjax">Retry to connect</a>',
            $html
        );
    }
}
