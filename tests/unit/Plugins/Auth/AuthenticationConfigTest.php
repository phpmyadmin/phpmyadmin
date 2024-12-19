<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function json_decode;

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

        $this->setLanguage();

        $this->setGlobalConfig();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$server = 2;
        Current::$database = 'db';
        Current::$table = 'table';
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

    public function testShowLoginFormWithoutAjax(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        ResponseRenderer::getInstance()->setAjax(false);
        self::assertNull($this->object->showLoginForm());
    }

    public function testShowLoginFormWithAjax(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        ResponseRenderer::getInstance()->setAjax(true);
        $response = $this->object->showLoginForm();
        self::assertNotNull($response);
        $body = (string) $response->getBody();
        self::assertJson($body);
        $json = json_decode($body, true);
        self::assertIsArray($json);
        self::assertArrayHasKey('reload_flag', $json);
        self::assertSame('1', $json['reload_flag']);
    }

    public function testAuthCheck(): void
    {
        Config::getInstance()->selectedServer = ['user' => 'username', 'password' => 'password'];
        self::assertTrue(
            $this->object->readCredentials(),
        );
    }

    public function testAuthSetUser(): void
    {
        self::assertTrue(
            $this->object->storeCredentials(),
        );
    }

    public function testAuthFails(): void
    {
        Config::getInstance()->settings['Servers'] = [1];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        $response = $this->object->showFailure(AuthenticationFailure::deniedByDatabaseServer());

        $html = (string) $response->getBody();

        self::assertStringContainsString(
            'You probably did not create a configuration file. You might want ' .
            'to use the <a href="setup/">setup script</a> to create one.',
            $html,
        );

        self::assertStringContainsString(
            '<strong>MySQL said: </strong><a href="index.php?route=/url&url=https%3A%2F%2F' .
            'dev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Fserver-error-reference.html"' .
            ' target="mysql_doc">' .
            '<img src="themes/dot.gif" title="Documentation" alt="Documentation" ' .
            'class="icon ic_b_help"></a>',
            $html,
        );

        self::assertStringContainsString('Cannot connect: invalid settings.', $html);

        self::assertStringContainsString(
            '<a href="index.php?route=/&server=2&lang=en" '
            . 'class="btn btn-primary mt-1 mb-1 disableAjax">Retry to connect</a>',
            $html,
        );
    }
}
