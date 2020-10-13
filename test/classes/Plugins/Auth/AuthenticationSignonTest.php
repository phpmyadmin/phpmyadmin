<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Auth\AuthenticationSignon;
use PhpMyAdmin\Tests\AbstractNetworkTestCase;
use function ob_get_clean;
use function ob_start;
use function phpversion;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function version_compare;

class AuthenticationSignonTest extends AbstractNetworkTestCase
{
    /** @var AuthenticationSignon */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $this->object = new AuthenticationSignon();
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
        $GLOBALS['cfg']['Server']['SignonURL'] = '';

        ob_start();
        $this->object->showLoginForm();
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            'You must set SignonURL!',
            $result
        );
    }

    public function testAuthLogoutURL(): void
    {
        $this->mockResponse('Location: https://example.com/logoutURL');

        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logoutURL';

        $this->object->logOut();
    }

    public function testAuthLogout(): void
    {
        $this->mockResponse('Location: https://example.com/SignonURL');

        $GLOBALS['header'] = [];
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['LogoutURL'] = '';

        $this->object->logOut();
    }

    public function testAuthCheckEmpty(): void
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'https://example.com/SignonDiffURL';

        $this->assertFalse(
            $this->object->readCredentials()
        );
    }

    public function testAuthCheckSession(): void
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['SignonScript'] = './examples/signon-script.php';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['SignonCookieParams'] = [];
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['port'] = '80';
        $GLOBALS['cfg']['Server']['user'] = 'user';

        $this->assertTrue(
            $this->object->readCredentials()
        );

        $this->assertEquals(
            'user',
            $this->object->user
        );

        $this->assertEquals(
            'password',
            $this->object->password
        );

        $this->assertEquals(
            'https://example.com/SignonURL',
            $_SESSION['LAST_SIGNON_URL']
        );
    }

    public function testAuthCheckToken(): void
    {
        $_SESSION = [' PMA_token ' => 'eefefef'];
        $this->mockResponse('Location: https://example.com/SignonURL');

        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['SignonCookieParams'] = [];
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['port'] = '80';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['SignonScript'] = '';
        $_COOKIE['session123'] = true;
        $_SESSION['PMA_single_signon_user'] = 'user123';
        $_SESSION['PMA_single_signon_password'] = 'pass123';
        $_SESSION['PMA_single_signon_host'] = 'local';
        $_SESSION['PMA_single_signon_port'] = '12';
        $_SESSION['PMA_single_signon_cfgupdate'] = ['foo' => 'bar'];
        $_SESSION['PMA_single_signon_token'] = 'pmaToken';
        $sessionName = session_name();
        $sessionID = session_id();

        $this->object->logOut();

        $this->assertEquals(
            [
                'SignonURL' => 'https://example.com/SignonURL',
                'SignonScript' => '',
                'SignonSession' => 'session123',
                'SignonCookieParams' => [],
                'host' => 'localhost',
                'port' => '80',
                'user' => 'user',
            ],
            $GLOBALS['cfg']['Server']
        );

        $this->assertEquals(
            $sessionName,
            session_name()
        );

        $this->assertEquals(
            $sessionID,
            session_id()
        );

        $this->assertArrayNotHasKey(
            'LAST_SIGNON_URL',
            $_SESSION
        );
    }

    public function testAuthCheckKeep(): void
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['SignonCookieParams'] = [];
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['port'] = '80';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['SignonScript'] = '';
        $_COOKIE['session123'] = true;
        $_REQUEST['old_usr'] = '';
        $_SESSION['PMA_single_signon_user'] = 'user123';
        $_SESSION['PMA_single_signon_password'] = 'pass123';
        $_SESSION['PMA_single_signon_host'] = 'local';
        $_SESSION['PMA_single_signon_port'] = '12';
        $_SESSION['PMA_single_signon_cfgupdate'] = ['foo' => 'bar'];
        $_SESSION['PMA_single_signon_token'] = 'pmaToken';

        $this->assertTrue(
            $this->object->readCredentials()
        );

        $this->assertEquals(
            'user123',
            $this->object->user
        );

        $this->assertEquals(
            'pass123',
            $this->object->password
        );
    }

    public function testAuthSetUser(): void
    {
        $this->object->user = 'testUser123';
        $this->object->password = 'testPass123';

        $this->assertTrue(
            $this->object->storeCredentials()
        );

        $this->assertEquals(
            'testUser123',
            $GLOBALS['cfg']['Server']['user']
        );

        $this->assertEquals(
            'testPass123',
            $GLOBALS['cfg']['Server']['password']
        );
    }

    public function testAuthFailsForbidden(): void
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder(AuthenticationSignon::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $this->object->showFailure('empty-denied');

        $this->assertEquals(
            'Login without a password is forbidden by configuration '
            . '(see AllowNoPassword)',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    public function testAuthFailsDeny(): void
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder(AuthenticationSignon::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $this->object->showFailure('allow-denied');

        $this->assertEquals(
            'Access denied!',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    public function testAuthFailsTimeout(): void
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder(AuthenticationSignon::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $GLOBALS['cfg']['LoginCookieValidity'] = '1440';

        $this->object->showFailure('no-activity');

        $this->assertEquals(
            'You have been automatically logged out due to inactivity of'
            . ' 1440 seconds. Once you log in again, you should be able to'
            . ' resume the work where you left off.',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    public function testAuthFailsMySQLError(): void
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder(AuthenticationSignon::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue('error<123>'));

        $GLOBALS['dbi'] = $dbi;

        $this->object->showFailure('');

        $this->assertEquals(
            'error&lt;123&gt;',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    public function testAuthFailsConnect(): void
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';
        unset($GLOBALS['errno']);

        $this->object = $this->getMockBuilder(AuthenticationSignon::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        $this->object->showFailure('');

        $this->assertEquals(
            'Cannot log in to the MySQL server',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    public function testSetCookieParamsDefaults(): void
    {
        $this->object = $this->getMockBuilder(AuthenticationSignon::class)
        ->disableOriginalConstructor()
        ->setMethods(['setCookieParams'])
        ->getMock();

        $this->object->setCookieParams([]);

        $defaultOptions = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
            'samesite' => '',
        ];
        // php did not set 'samesite' attribute in session_get_cookie_params since not yet implemented
        if (version_compare((string) phpversion(), '7.3.0', '<')) {
            unset($defaultOptions['samesite']);
        }

        $this->assertSame(
            $defaultOptions,
            session_get_cookie_params()
        );
    }
}
