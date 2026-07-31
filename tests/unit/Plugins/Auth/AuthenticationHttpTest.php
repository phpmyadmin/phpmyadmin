<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Plugins\Auth\AuthenticationHttp;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseRendererStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function base64_encode;
use function json_decode;

#[CoversClass(AuthenticationHttp::class)]
#[Medium]
class AuthenticationHttpTest extends AbstractTestCase
{
    protected AuthenticationHttp $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Config::getInstance()->settings['Servers'] = [];
        Current::$database = 'db';
        Current::$table = 'table';
        Current::$lang = 'en';
        $this->object = new AuthenticationHttp(new ResponseRendererStub());
    }

    public function testAuthLogoutUrl(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['auth_type'] = 'http';
        $config->selectedServer['LogoutURL'] = 'https://example.com/logout';

        $response = $this->object->logOut();

        self::assertSame(['https://example.com/logout'], $response->getHeader('Location'));
        self::assertSame(302, $response->getStatusCode());
    }

    public function testAuthVerbose(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['auth_type'] = 'http';
        $config->selectedServer['verbose'] = 'verboseMessagê';

        $response = $this->object->showLoginForm();

        self::assertSame(['Basic realm="phpMyAdmin verboseMessag"'], $response->getHeader('WWW-Authenticate'));
        self::assertSame(401, $response->getStatusCode());
    }

    public function testAuthHost(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['auth_type'] = 'http';
        $config->selectedServer['verbose'] = '';
        $config->selectedServer['host'] = 'hòst';

        $response = $this->object->showLoginForm();

        self::assertSame(['Basic realm="phpMyAdmin hst"'], $response->getHeader('WWW-Authenticate'));
        self::assertSame(401, $response->getStatusCode());
    }

    public function testAuthRealm(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['auth_type'] = 'http';
        $config->selectedServer['host'] = '';
        $config->selectedServer['auth_http_realm'] = 'rêäealmmessage';

        $response = $this->object->showLoginForm();

        self::assertSame(['Basic realm="realmmessage"'], $response->getHeader('WWW-Authenticate'));
        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * @param string      $user           test username
     * @param string      $pass           test password
     * @param string      $userIndex      index to test username against
     * @param string      $passIndex      index to test username against
     * @param string|bool $expectedReturn expected return value from test
     * @param string      $expectedUser   expected username to be set
     * @param string      $expectedPass   expected password to be set
     * @param string|bool $oldUsr         value for $_REQUEST['old_usr']
     */
    #[DataProvider('readCredentialsProvider')]
    public function testAuthCheck(
        string $user,
        string $pass,
        string $userIndex,
        string $passIndex,
        string|bool $expectedReturn,
        string $expectedUser,
        string $expectedPass,
        string|bool $oldUsr = '',
    ): void {
        $_SERVER[$userIndex] = $user;
        $_SERVER[$passIndex] = $pass;

        $_REQUEST['old_usr'] = $oldUsr;

        self::assertSame(
            $expectedReturn,
            $this->object->readCredentials(),
        );

        self::assertSame($expectedUser, $this->object->user);

        self::assertSame($expectedPass, $this->object->password);

        unset($_SERVER[$userIndex]);
        unset($_SERVER[$passIndex]);
    }

    /**
     * @return array<array{
     *     0: string, 1: string, 2: string, 3: string, 4: string|bool, 5: string, 6: string|bool, 7?: string|bool
     * }>
     */
    public static function readCredentialsProvider(): array
    {
        return [
            ['Basic ' . base64_encode('foo:bar'), 'pswd', 'PHP_AUTH_USER', 'PHP_AUTH_PW', false, '', 'bar', 'foo'],
            [
                'Basic ' . base64_encode('foobar'),
                'pswd',
                'REMOTE_USER',
                'REMOTE_PASSWORD',
                true,
                'Basic Zm9vYmFy',
                'pswd',
            ],
            ['Basic ' . base64_encode('foobar:'), 'pswd', 'AUTH_USER', 'AUTH_PASSWORD', true, 'foobar', ''],
            [
                'Basic ' . base64_encode(':foobar'),
                'pswd',
                'HTTP_AUTHORIZATION',
                'AUTH_PASSWORD',
                true,
                'Basic OmZvb2Jhcg==',
                'pswd',
            ],
            ['BasicTest', 'pswd', 'Authorization', 'AUTH_PASSWORD', true, 'BasicTest', 'pswd'],
        ];
    }

    public function testAuthSetUser(): void
    {
        // case 1

        $this->object->user = 'testUser';
        $this->object->password = 'testPass';
        Current::$server = 2;
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'testUser';

        self::assertTrue(
            $this->object->storeCredentials(),
        );

        self::assertSame('testUser', $config->selectedServer['user']);

        self::assertSame('testPass', $config->selectedServer['password']);

        self::assertArrayNotHasKey('PHP_AUTH_PW', $_SERVER);

        self::assertSame(2, Current::$server);

        // case 2
        $this->object->user = 'testUser';
        $this->object->password = 'testPass';
        $config->settings['Servers'][1] = ['host' => 'a', 'user' => 'testUser', 'foo' => 'bar'];

        $config->selectedServer = ['host' => 'a', 'user' => 'user2'];

        self::assertTrue(
            $this->object->storeCredentials(),
        );

        self::assertEquals(
            ['user' => 'testUser', 'password' => 'testPass', 'host' => 'a'],
            $config->selectedServer,
        );

        self::assertSame(2, Current::$server);

        // case 3
        Current::$server = 3;
        $this->object->user = 'testUser';
        $this->object->password = 'testPass';
        $config->settings['Servers'][1] = ['host' => 'a', 'user' => 'testUsers', 'foo' => 'bar'];

        $config->selectedServer = ['host' => 'a', 'user' => 'user2'];

        self::assertTrue(
            $this->object->storeCredentials(),
        );

        self::assertEquals(
            ['user' => 'testUser', 'password' => 'testPass', 'host' => 'a'],
            $config->selectedServer,
        );

        self::assertSame(3, Current::$server);
    }

    public function testAuthFails(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['host'] = '';
        $_REQUEST = [];
        Current::$server = 0;

        $dbi = $this->createMock(DatabaseInterface::class);

        $dbi->expects(self::exactly(3))
            ->method('getError')
            ->willReturn('error 123', 'error 321', '');

        DatabaseInterface::$instance = $dbi;
        DatabaseInterface::$errorNumber = 31;

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);
        $this->object = new AuthenticationHttp($responseRenderer);

        $response = $this->object->showFailure(AuthenticationFailure::deniedByDatabaseServer());

        $result = (string) $response->getBody();
        self::assertStringContainsString('<p>error 123</p>', $result);

        // case 2
        $config->selectedServer['host'] = 'host';
        DatabaseInterface::$errorNumber = 1045;

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);
        $this->object = new AuthenticationHttp($responseRenderer);

        $response = $this->object->showFailure(AuthenticationFailure::deniedByDatabaseServer());
        $result = (string) $response->getBody();
        self::assertStringContainsString('Wrong username/password. Access denied.', $result);

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);
        $this->object = new AuthenticationHttp($responseRenderer);

        // case 3
        DatabaseInterface::$errorNumber = 1043;
        $response = $this->object->showFailure(AuthenticationFailure::deniedByDatabaseServer());
        $result = (string) $response->getBody();
        self::assertStringContainsString('Wrong username/password. Access denied.', $result);
    }

    public function testShowLoginFormWithAjax(): void
    {
        Current::$database = '';
        Current::$table = '';
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(true);
        $response = (new AuthenticationHttp($responseRenderer))->showLoginForm();

        $body = (string) $response->getBody();
        self::assertJson($body);
        $json = json_decode($body, true);
        self::assertIsArray($json);
        self::assertArrayHasKey('reload_flag', $json);
        self::assertSame('1', $json['reload_flag']);
    }
}
