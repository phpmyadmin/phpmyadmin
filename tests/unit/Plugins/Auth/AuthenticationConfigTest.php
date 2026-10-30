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
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseRendererStub;
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

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$server = 2;
        Current::$database = 'db';
        Current::$table = 'table';
        $this->object = new AuthenticationConfig(new ResponseRendererStub());
    }

    public function testShowLoginFormWithoutAjax(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);
        $this->object = new AuthenticationConfig($responseRenderer);
        self::assertNull($this->object->showLoginForm());
    }

    public function testShowLoginFormWithAjax(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(true);
        $this->object = new AuthenticationConfig($responseRenderer);
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

        $dbi = $this->createStub(DatabaseInterface::class);
        DatabaseInterface::$instance = $dbi;

        $response = $this->object->showFailure(AuthenticationFailure::deniedByDatabaseServer());

        $html = (string) $response->getBody();

        self::assertStringContainsString(
            'You probably did not create a configuration file. You might want ' .
            'to use the <a href="setup/">setup script</a> to create one.',
            $html,
        );

        self::assertStringContainsString(
            '<a href="index.php?route=/&server=2&lang=en" '
            . 'class="btn btn-primary mt-1 mb-1 disableAjax">Retry to connect</a>',
            $html,
        );
    }
}
