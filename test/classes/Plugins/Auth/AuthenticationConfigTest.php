<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\Tests\AbstractTestCase;
use function ob_get_clean;
use function ob_start;

class AuthenticationConfigTest extends AbstractTestCase
{
    /** @var AuthenticationConfig */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
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

    public function testAuth(): void
    {
        $this->assertTrue(
            $this->object->showLoginForm()
        );
    }

    public function testAuthCheck(): void
    {
        $GLOBALS['cfg']['Server'] = [
            'user' => 'username',
            'password' => 'password',
        ];
        $this->assertTrue(
            $this->object->readCredentials()
        );
    }

    public function testAuthSetUser(): void
    {
        $this->assertTrue(
            $this->object->storeCredentials()
        );
    }

    public function testAuthFails(): void
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

        $this->assertIsString($html);

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
            '<a href="index.php?route=/&server=0&lang=en" '
            . 'class="btn button mt-1 disableAjax">Retry to connect</a>',
            $html
        );
    }
}
