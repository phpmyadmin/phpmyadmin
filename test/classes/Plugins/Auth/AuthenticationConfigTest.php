<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use Throwable;

use function ob_get_clean;
use function ob_start;

#[CoversClass(AuthenticationConfig::class)]
#[Medium]
class AuthenticationConfigTest extends AbstractTestCase
{
    protected AuthenticationConfig $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        parent::setGlobalConfig();

        parent::setTheme();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuth(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        ResponseRenderer::getInstance()->setAjax(true);
        $this->expectException(ExitException::class);
        $this->object->showLoginForm();
    }

    public function testAuthCheck(): void
    {
        Config::getInstance()->selectedServer = ['user' => 'username', 'password' => 'password'];
        $this->assertTrue(
            $this->object->readCredentials(),
        );
    }

    public function testAuthSetUser(): void
    {
        $this->assertTrue(
            $this->object->storeCredentials(),
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthFails(): void
    {
        Config::getInstance()->settings['Servers'] = [1];
        $GLOBALS['allowDeny_forbidden'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        ob_start();
        try {
            $this->object->showFailure('');
        } catch (Throwable $throwable) {
        }

        $html = ob_get_clean();

        $this->assertInstanceOf(ExitException::class, $throwable);

        $this->assertIsString($html);

        $this->assertStringContainsString(
            'You probably did not create a configuration file. You might want ' .
            'to use the <a href="setup/">setup script</a> to create one.',
            $html,
        );

        $this->assertStringContainsString(
            '<strong>MySQL said: </strong><a href="index.php?route=/url&url=https%3A%2F%2F' .
            'dev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fserver-error-reference.html"' .
            ' target="mysql_doc">' .
            '<img src="themes/dot.gif" title="Documentation" alt="Documentation" ' .
            'class="icon ic_b_help"></a>',
            $html,
        );

        $this->assertStringContainsString('Cannot connect: invalid settings.', $html);

        $this->assertStringContainsString(
            '<a href="index.php?route=/&server=0&lang=en" '
            . 'class="btn btn-primary mt-1 mb-1 disableAjax">Retry to connect</a>',
            $html,
        );
    }
}
