<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Plugins\Auth\AuthenticationSignon;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use function json_decode;

/**
 * Tests the script based signon authentication.
 *
 * Signon scripts declare a global function, so every test needs its own process.
 */
#[CoversClass(AuthenticationSignon::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class AuthenticationSignonScriptTest extends AbstractTestCase
{
    private const IDENTITY_SCRIPT = __DIR__ . '/../../../test_data/signon/identity-script.php';
    private const LEGACY_SCRIPT = __DIR__ . '/../../../test_data/signon/legacy-script.php';
    private const TYPED_PARAMETER_SCRIPT = __DIR__ . '/../../../test_data/signon/typed-parameter-script.php';

    private AuthenticationSignon $object;

    private Config $config;

    private ResponseRenderer $responseRenderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $_SESSION = [];
        $GLOBALS['signon_test_calls'] = [];
        $GLOBALS['signon_test_users'] = [
            1 => ['alice' => ['alice_db1', 'secret1']],
            2 => ['alice' => ['alice_db2', 'secret2'], 'bob' => ['bob_db2', 'secret3']],
            3 => ['bob' => ['bob_db3', 'secret4']],
        ];

        $this->config = new Config();
        Config::$instance = $this->config;
        $this->useServers(self::IDENTITY_SCRIPT, 1);

        $this->responseRenderer = new ResponseRenderer();
        $this->object = new AuthenticationSignon($this->responseRenderer);
    }

    /**
     * Configures three servers using the given script, plus a cookie server and a disabled server.
     *
     * @param int<1, 3> $current
     */
    private function useServers(string $script, int $current, string $logoutUrl = ''): void
    {
        $servers = [
            1 => ['host' => 'db1.example.com', 'verbose' => 'First <server>'],
            2 => ['host' => 'db2.example.com'],
            3 => ['host' => 'db3.example.com', 'verbose' => 'Third'],
            4 => ['host' => 'db4.example.com', 'auth_type' => 'cookie'],
            5 => ['host' => ''],
        ];
        foreach ($servers as $number => $server) {
            $this->config->settings['Servers'][$number] = (new Server($server + [
                'auth_type' => 'signon',
                'SignonScript' => $script,
                'LogoutURL' => $logoutUrl,
            ]))->asArray();
        }

        Current::$server = $current;
        $this->config->selectedServer = $this->config->settings['Servers'][$current];
    }

    /**
     * Reads the session the way the next request would, without static analysis assuming its contents.
     */
    private function session(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    private function request(string $method, string|null $identity, string|null $server = null): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        if ($identity !== null) {
            $_SERVER['HTTP_X_TEST_IDENTITY'] = $identity;
        }

        if ($server === null) {
            return;
        }

        $_GET['server'] = $server;
    }

    /** @return array<array-key, mixed> */
    private function json(Response|null $response): array
    {
        self::assertNotNull($response);
        $json = json_decode((string) $response->getBody(), true);
        self::assertIsArray($json);

        return $json;
    }

    private function assertNotCacheable(Response $response): void
    {
        self::assertStringContainsString('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('no-cache', $response->getHeaderLine('Pragma'));
    }

    public function testLogsInMappedIdentity(): void
    {
        $this->request('GET', 'alice');

        self::assertNull($this->object->authenticate());
        self::assertSame('alice_db1', $this->config->selectedServer['user']);
        self::assertSame('secret1', $this->config->selectedServer['password']);
        self::assertSame('alice', $this->session('PMA_single_signon_identity'));
        self::assertNull($this->session('LAST_SIGNON_URL'));
    }

    public function testServerNumberIsPassedToScript(): void
    {
        $this->useServers(self::IDENTITY_SCRIPT, 2);
        $this->request('GET', 'alice', '2');

        self::assertNull($this->object->authenticate());
        self::assertSame('alice_db2', $this->config->selectedServer['user']);
        self::assertSame([2], $GLOBALS['signon_test_calls']);
    }

    public function testServerNumberIsNotPassedToScriptWithOwnSecondParameter(): void
    {
        $this->useServers(self::TYPED_PARAMETER_SCRIPT, 2);
        $this->request('GET', null, '2');

        self::assertNull($this->object->authenticate());
        self::assertSame('realm-default', $this->config->selectedServer['user']);
    }

    public function testLegacyScriptKeepsWorking(): void
    {
        $this->useServers(self::LEGACY_SCRIPT, 1);
        $GLOBALS['signon_test_legacy_credentials'] = ['legacy', 'pass'];
        $_SESSION[' PMA_token '] = 'token';
        $this->request('POST', null);

        self::assertNull($this->object->authenticate());
        self::assertSame('legacy', $this->config->selectedServer['user']);
        // Extra elements in the list are not taken for an identity, so the session and the request survive.
        self::assertSame('token', $this->session(' PMA_token '));
        self::assertNull($this->session('PMA_single_signon_identity'));
    }

    public function testLegacyScriptRefusalIsNotProbedForOtherServers(): void
    {
        $this->useServers(self::LEGACY_SCRIPT, 1);
        $this->request('GET', null);

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('Access denied!', (string) $response->getBody());
        // Only the selected server was asked, with the user name from its configuration.
        self::assertSame(['root'], $GLOBALS['signon_test_calls']);
    }

    public function testUserWithoutLoginIsSentToServerTheyCanUse(): void
    {
        $this->request('GET', 'bob');

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('index.php?route=/', $response->getHeaderLine('Location'));
        self::assertStringContainsString('server=2', $response->getHeaderLine('Location'));
        $this->assertNotCacheable($response);
    }

    public function testUnsafeRequestWithoutChosenServerIsRedirectedWithoutBeingCarriedOut(): void
    {
        $this->request('POST', 'bob');
        $_SESSION['PMA_single_signon_identity'] = 'bob';

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(303, $response->getStatusCode());
        self::assertStringContainsString('server=2', $response->getHeaderLine('Location'));
        self::assertNotSame('bob_db2', $this->config->selectedServer['user']);
    }

    public function testDeepLinkToOtherServerIsNotRedirected(): void
    {
        $this->request('GET', 'bob');
        $_GET['route'] = '/database/structure';
        $_GET['db'] = 'orders';

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('You can log in to the following servers:', (string) $response->getBody());
    }

    /**
     * @param array<string, string> $get
     * @param array<string, string> $cookie
     */
    #[DataProvider('providerForServerSelection')]
    public function testOnlyRequestParametersCountAsChoosingTheServer(
        array $get,
        array $cookie,
        int $expectedStatus,
    ): void {
        $this->request('GET', 'bob');
        $_GET += $get;
        $_COOKIE = $cookie;
        $_REQUEST = $get + $cookie;

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame($expectedStatus, $response->getStatusCode());
    }

    /** @return iterable<string, array{array<string, string>, array<string, string>, int}> */
    public static function providerForServerSelection(): iterable
    {
        yield 'no parameter' => [[], [], 302];
        yield 'empty parameter' => [['server' => ''], [], 302];
        yield 'cookie named server' => [[], ['server' => '1'], 302];
        yield 'explicit parameter' => [['server' => '1'], [], 403];
    }

    public function testUserWithoutLoginOnChosenServerIsToldWhereTheyCanLogIn(): void
    {
        $this->useServers(self::IDENTITY_SCRIPT, 1, 'https://pma.example.com/oauth2/sign_out?rd=%2F&x="y"');
        $this->request('GET', 'bob', '1');

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        $body = (string) $response->getBody();
        self::assertSame(403, $response->getStatusCode());
        $this->assertNotCacheable($response);
        self::assertStringContainsString('No login is configured for <strong>bob</strong> on this server.', $body);
        self::assertStringContainsString(
            'You can log in to the following servers: '
            . '<a href="index.php?route=/&server=2&lang=en">db2.example.com</a>, '
            . '<a href="index.php?route=/&server=3&lang=en">Third</a>',
            $body,
        );
        self::assertStringNotContainsString('db4.example.com', $body);
        self::assertStringContainsString(
            '<a href="https://pma.example.com/oauth2/sign_out?rd=%2F&amp;x=&quot;y&quot;">'
            . 'Log in as a different user</a>',
            $body,
        );
    }

    public function testServerNamesAreEscaped(): void
    {
        $this->useServers(self::IDENTITY_SCRIPT, 3);
        $this->request('GET', 'alice', '3');

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertStringContainsString('First &lt;server&gt;', (string) $response->getBody());
    }

    public function testUnknownIdentityIsNamedAndNotOfferedServers(): void
    {
        $this->request('GET', '<i>mallory');

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        $body = (string) $response->getBody();
        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString(
            'No login is configured for <strong>&lt;i&gt;mallory</strong> on this server.',
            $body,
        );
        self::assertStringNotContainsString('You can log in to the following servers:', $body);
        self::assertStringNotContainsString('Log in as a different user', $body);
    }

    public function testRequestWithoutIdentityIsRefusedAndGetsNewSession(): void
    {
        $this->request('GET', null);
        $_SESSION[' PMA_token '] = 'old-token';

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode());
        self::assertStringContainsString('Access denied!', (string) $response->getBody());
        self::assertNotSame('old-token', $this->session(' PMA_token '));
        // Only the selected server was asked.
        self::assertSame([1], $GLOBALS['signon_test_calls']);
    }

    public function testAjaxRequestGetsJsonError(): void
    {
        $this->responseRenderer->setAjax(true);
        $this->request('GET', 'bob');

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame('', $response->getHeaderLine('Location'));
        $json = $this->json($response);
        self::assertFalse($json['success']);
        self::assertSame('1', $json['reload_flag']);
        self::assertIsString($json['error']);
        self::assertStringContainsString('No login is configured for <strong>bob</strong>', $json['error']);
    }

    public function testAjaxErrorDoesNotInterpretMarkupInIdentity(): void
    {
        $this->responseRenderer->setAjax(true);
        $this->request('GET', '[strong]mallory[/strong]');

        $json = $this->json($this->object->authenticate());

        self::assertIsString($json['error']);
        self::assertStringContainsString('[strong]mallory[/strong]', $json['error']);
    }

    public function testDatabaseRefusalNamesOtherServers(): void
    {
        $this->useServers(self::IDENTITY_SCRIPT, 2);
        $this->request('GET', 'bob', '2');
        self::assertTrue($this->object->readCredentials());

        $response = $this->object->showFailure(AuthenticationFailure::rootDeniedByConfiguration());

        $body = (string) $response->getBody();
        self::assertSame(403, $response->getStatusCode());
        $this->assertNotCacheable($response);
        self::assertStringContainsString(AuthenticationFailure::rootDeniedByConfiguration()->getMessage(), $body);
        self::assertStringContainsString('server=3', $body);
    }

    public function testRefusedLoginRedirectsToSignonUrlWhenSet(): void
    {
        $this->config->selectedServer['SignonURL'] = 'https://example.com/signon';
        $this->request('GET', null);

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://example.com/signon', $response->getHeaderLine('Location'));
        $this->assertNotCacheable($response);
    }

    public function testStaleSignonUrlFromOtherServerDoesNotPreventLogin(): void
    {
        $_SESSION['LAST_SIGNON_URL'] = 'https://sso.example.com/login';
        $this->request('GET', 'alice');

        self::assertNull($this->object->authenticate());
        self::assertSame('alice_db1', $this->config->selectedServer['user']);
    }

    public function testStaleSignonUrlFromOtherServerDoesNotBreakRefusal(): void
    {
        $_SESSION['LAST_SIGNON_URL'] = 'https://sso.example.com/login';
        $this->request('GET', 'bob');

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('server=2', $response->getHeaderLine('Location'));
    }

    public function testIdentityWithoutLoginKeepsItsSession(): void
    {
        $this->request('GET', 'bob', '1');
        $_SESSION['PMA_single_signon_identity'] = 'bob';
        $_SESSION[' PMA_token '] = 'token-of-bob';

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('token-of-bob', $this->session(' PMA_token '));
    }

    /**
     * A request that passed the token check of a session belonging to nobody or to somebody else
     * must not be carried out for the current identity.
     */
    #[DataProvider('providerForForeignSessions')]
    public function testUnsafeRequestOnForeignSessionIsNotCarriedOut(string|null $sessionIdentity, string $method): void
    {
        $this->request($method, 'alice');
        $_SESSION[' PMA_token '] = 'token-known-to-attacker';
        if ($sessionIdentity !== null) {
            $_SESSION['PMA_single_signon_identity'] = $sessionIdentity;
        }

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame(303, $response->getStatusCode());
        self::assertStringContainsString('index.php?route=/', $response->getHeaderLine('Location'));
        $this->assertNotCacheable($response);
        self::assertNotSame('token-known-to-attacker', $this->session(' PMA_token '));
        self::assertSame('alice', $this->session('PMA_single_signon_identity'));
        self::assertNotSame('alice_db1', $this->config->selectedServer['user']);
    }

    /** @return iterable<string, array{string|null, string}> */
    public static function providerForForeignSessions(): iterable
    {
        yield 'session without identity' => [null, 'POST'];
        yield 'session of another identity' => ['bob', 'POST'];
        yield 'other unsafe method' => ['bob', 'DELETE'];
    }

    public function testAjaxRequestOnForeignSessionGetsJsonError(): void
    {
        $this->responseRenderer->setAjax(true);
        $this->request('POST', 'alice');
        $_SESSION['PMA_single_signon_identity'] = 'bob';

        $response = $this->object->authenticate();

        self::assertNotNull($response);
        self::assertSame('', $response->getHeaderLine('Location'));
        $json = $this->json($response);
        self::assertFalse($json['success']);
        self::assertSame('1', $json['reload_flag']);
        self::assertNotSame('alice_db1', $this->config->selectedServer['user']);
    }

    #[DataProvider('providerForSafeMethods')]
    public function testSafeRequestOnForeignSessionGetsNewSession(string $method): void
    {
        $this->request($method, 'alice');
        $_SESSION['PMA_single_signon_identity'] = 'bob';
        $_SESSION[' PMA_token '] = 'token-of-bob';

        self::assertNull($this->object->authenticate());
        self::assertNotSame('token-of-bob', $this->session(' PMA_token '));
        self::assertSame('alice', $this->session('PMA_single_signon_identity'));
        self::assertSame('alice_db1', $this->config->selectedServer['user']);
    }

    /** @return iterable<string, array{string}> */
    public static function providerForSafeMethods(): iterable
    {
        yield 'GET' => ['GET'];
        yield 'HEAD' => ['HEAD'];
    }

    public function testUnsafeRequestOnOwnSessionIsCarriedOut(): void
    {
        $this->request('POST', 'alice');
        $_SESSION['PMA_single_signon_identity'] = 'alice';
        $_SESSION[' PMA_token '] = 'token-of-alice';

        self::assertNull($this->object->authenticate());
        self::assertSame('token-of-alice', $this->session(' PMA_token '));
        self::assertSame('alice_db1', $this->config->selectedServer['user']);
    }

    public function testSecondFactorIsCheckedAgainForAnotherDatabaseUser(): void
    {
        $_SESSION['PMA_single_signon_identity'] = 'alice';
        $_SESSION['two_factor_check'] = true;
        $_SESSION['PMA_single_signon_2fa_scope'] = '1/alice_db1';

        $this->request('GET', 'alice');
        self::assertNull($this->object->authenticate());
        self::assertTrue($this->session('two_factor_check'));

        $this->useServers(self::IDENTITY_SCRIPT, 2);
        $this->request('GET', 'alice', '2');
        self::assertNull((new AuthenticationSignon(new ResponseRenderer()))->authenticate());
        self::assertNull($this->session('two_factor_check'));
        self::assertSame('2/alice_db2', $this->session('PMA_single_signon_2fa_scope'));
    }

    public function testScriptCanBeUsedTwiceInOneRequest(): void
    {
        $this->request('GET', 'alice');

        self::assertTrue($this->object->readCredentials());
        self::assertTrue($this->object->readCredentials());
        self::assertSame('alice_db1', $this->object->user);
    }

    public function testLogoutWithoutAnyUrlGoesToStartPage(): void
    {
        $response = $this->object->logOut();

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('index.php?route=/', $response->getHeaderLine('Location'));
    }

    public function testLogoutGoesToLogoutUrl(): void
    {
        $this->config->selectedServer['LogoutURL'] = 'https://pma.example.com/oauth2/sign_out';

        $response = $this->object->logOut();

        self::assertSame('https://pma.example.com/oauth2/sign_out', $response->getHeaderLine('Location'));
    }
}
