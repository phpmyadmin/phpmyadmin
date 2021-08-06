<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use Lcobucci\JWT\Token;
use PhpMyAdmin\Plugins\Auth\AuthenticationKeycloak;
use PhpMyAdmin\Tests\AbstractNetworkTestCase;

class AuthenticationKeycloakTest extends AbstractNetworkTestCase
{
    /** @var AuthenticationKeycloak */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        parent::setTheme();
        $GLOBALS['cfg']['Servers'] = [];
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['token_provided'] = true;
        $GLOBALS['token_mismatch'] = false;
        $this->object = new AuthenticationKeycloak();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    public function testShowLoginForm(): void
    {
        $this->assertFalse(
            $this->object->showLoginForm()
        );
    }

    public function testReadCredentialsWithoutCookie(): void
    {
        $this->assertFalse(
            $this->object->readCredentials()
        );
    }

    public function testReadCredentialsWithEmptyCookie(): void
    {
        $_COOKIE['kc-access'] = "";

        $this->assertFalse(
            $this->object->readCredentials()
        );

        unset($_COOKIE['kc-access']);
    }

    public function testReadCredentialsWithoutAllFields()
    {
        $_COOKIE['kc-access'] = "unit-test";
        $token = \Mockery::mock(Token::class);

        $authenticationMok = \Mockery::mock(AuthenticationKeycloak::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $authenticationMok->shouldReceive('getToken')->with("unit-test")->andReturn($token);
        $token->shouldReceive('getClaim')->with('preferred_username')->once()->andReturn('username');
        $token->shouldReceive('getClaim')->with('sub')->once()->andReturnNull();

        $this->assertFalse($authenticationMok->readCredentials());
        unset($_COOKIE['kc-access']);
    }

    public function testReadCredentials(): void
    {
        $_COOKIE['kc-access'] = "unit-test";
        $token = \Mockery::mock(Token::class);

        $authenticationMok = \Mockery::mock(AuthenticationKeycloak::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $authenticationMok->shouldReceive('getToken')->with("unit-test")->andReturn($token);
        $token->shouldReceive('getClaim')->with('preferred_username')->twice()->andReturn('username');
        $token->shouldReceive('getClaim')->with('sub')->twice()->andReturn('sub');

        $this->assertTrue($authenticationMok->readCredentials());
        unset($_COOKIE['kc-access']);
    }
}
