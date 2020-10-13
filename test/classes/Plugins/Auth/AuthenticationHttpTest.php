<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\Plugins\Auth\AuthenticationHttp;
use PhpMyAdmin\Tests\AbstractNetworkTestCase;
use function base64_encode;
use function ob_get_clean;
use function ob_start;

class AuthenticationHttpTest extends AbstractNetworkTestCase
{
    /** @var AuthenticationHttp */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        parent::setTheme();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Servers'] = [];
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['token_provided'] = true;
        $GLOBALS['token_mismatch'] = false;
        $this->object = new AuthenticationHttp();
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
     * @param mixed   $set_minimal set minimal
     * @param mixed   $body_id     body id
     * @param mixed   $set_title   set title
     * @param mixed[] ...$headers  headers
     */
    public function doMockResponse($set_minimal, $body_id, $set_title, ...$headers): void
    {
        // mock footer
        $mockFooter = $this->getMockBuilder(Footer::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMinimal'])
            ->getMock();

        $mockFooter->expects($this->exactly($set_minimal))
            ->method('setMinimal')
            ->with();

        // mock header

        $mockHeader = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setBodyId',
                    'setTitle',
                    'disableMenuAndConsole',
                    'addHTML',
                ]
            )
            ->getMock();

        $mockHeader->expects($this->exactly($body_id))
            ->method('setBodyId')
            ->with('loginform');

        $mockHeader->expects($this->exactly($set_title))
            ->method('setTitle')
            ->with('Access denied!');

        $mockHeader->expects($this->exactly($set_title))
            ->method('disableMenuAndConsole')
            ->with();

        // set mocked headers and footers
        $mockResponse = $this->mockResponse($headers);

        $mockResponse->expects($this->exactly($set_title))
            ->method('getFooter')
            ->with()
            ->will($this->returnValue($mockFooter));

        $mockResponse->expects($this->exactly($set_title))
            ->method('getHeader')
            ->with()
            ->will($this->returnValue($mockHeader));

        $mockResponse->expects($this->exactly($set_title * 7))
            ->method('addHTML')
            ->with();

        if (! empty($_REQUEST['old_usr'])) {
            $this->object->logOut();
        } else {
            $this->assertFalse(
                $this->object->showLoginForm()
            );
        }
    }

    public function testAuthLogoutUrl(): void
    {
        $_REQUEST['old_usr'] = '1';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logout';

        $this->doMockResponse(
            0,
            0,
            0,
            ['Location: https://example.com/logout']
        );
    }

    public function testAuthVerbose(): void
    {
        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['Server']['verbose'] = 'verboseMessagê';

        $this->doMockResponse(
            1,
            1,
            1,
            ['WWW-Authenticate: Basic realm="phpMyAdmin verboseMessag"'],
            ['status: 401 Unauthorized'],
            401
        );
    }

    public function testAuthHost(): void
    {
        $GLOBALS['cfg']['Server']['verbose'] = '';
        $GLOBALS['cfg']['Server']['host'] = 'hòst';

        $this->doMockResponse(
            1,
            1,
            1,
            ['WWW-Authenticate: Basic realm="phpMyAdmin hst"'],
            ['status: 401 Unauthorized'],
            401
        );
    }

    public function testAuthRealm(): void
    {
        $GLOBALS['cfg']['Server']['host'] = '';
        $GLOBALS['cfg']['Server']['auth_http_realm'] = 'rêäealmmessage';

        $this->doMockResponse(
            1,
            1,
            1,
            ['WWW-Authenticate: Basic realm="realmmessage"'],
            ['status: 401 Unauthorized'],
            401
        );
    }

    /**
     * @param string      $user           test username
     * @param string      $pass           test password
     * @param string      $userIndex      index to test username against
     * @param string      $passIndex      index to test username against
     * @param string|bool $expectedReturn expected return value from test
     * @param string      $expectedUser   expected username to be set
     * @param string|bool $expectedPass   expected password to be set
     * @param string|bool $old_usr        value for $_REQUEST['old_usr']
     *
     * @dataProvider readCredentialsProvider
     */
    public function testAuthCheck(
        string $user,
        string $pass,
        string $userIndex,
        string $passIndex,
        $expectedReturn,
        string $expectedUser,
        $expectedPass,
        $old_usr = ''
    ): void {
        $_SERVER[$userIndex] = $user;
        $_SERVER[$passIndex] = $pass;

        $_REQUEST['old_usr'] = $old_usr;

        $this->assertEquals(
            $expectedReturn,
            $this->object->readCredentials()
        );

        $this->assertEquals(
            $expectedUser,
            $this->object->user
        );

        $this->assertEquals(
            $expectedPass,
            $this->object->password
        );

        $_SERVER[$userIndex] = null;
        $_SERVER[$passIndex] = null;
    }

    /**
     * Data provider for testAuthCheck
     *
     * @return array Test data
     */
    public function readCredentialsProvider(): array
    {
        return [
            [
                'Basic ' . base64_encode('foo:bar'),
                'pswd',
                'PHP_AUTH_USER',
                'PHP_AUTH_PW',
                false,
                '',
                'bar',
                'foo',
            ],
            [
                'Basic ' . base64_encode('foobar'),
                'pswd',
                'REMOTE_USER',
                'REMOTE_PASSWORD',
                true,
                'Basic Zm9vYmFy',
                'pswd',
            ],
            [
                'Basic ' . base64_encode('foobar:'),
                'pswd',
                'AUTH_USER',
                'AUTH_PASSWORD',
                true,
                'foobar',
                false,
            ],
            [
                'Basic ' . base64_encode(':foobar'),
                'pswd',
                'HTTP_AUTHORIZATION',
                'AUTH_PASSWORD',
                true,
                'Basic OmZvb2Jhcg==',
                'pswd',
            ],
            [
                'BasicTest',
                'pswd',
                'Authorization',
                'AUTH_PASSWORD',
                true,
                'BasicTest',
                'pswd',
            ],
        ];
    }

    public function testAuthSetUser(): void
    {
        // case 1

        $this->object->user = 'testUser';
        $this->object->password = 'testPass';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['Server']['user'] = 'testUser';

        $this->assertTrue(
            $this->object->storeCredentials()
        );

        $this->assertEquals(
            'testUser',
            $GLOBALS['cfg']['Server']['user']
        );

        $this->assertEquals(
            'testPass',
            $GLOBALS['cfg']['Server']['password']
        );

        $this->assertArrayNotHasKey(
            'PHP_AUTH_PW',
            $_SERVER
        );

        $this->assertEquals(
            2,
            $GLOBALS['server']
        );

        // case 2
        $this->object->user = 'testUser';
        $this->object->password = 'testPass';
        $GLOBALS['cfg']['Servers'][1] = [
            'host' => 'a',
            'user' => 'testUser',
            'foo' => 'bar',
        ];

        $GLOBALS['cfg']['Server'] = [
            'host' => 'a',
            'user' => 'user2',
        ];

        $this->assertTrue(
            $this->object->storeCredentials()
        );

        $this->assertEquals(
            [
                'user' => 'testUser',
                'password' => 'testPass',
                'host' => 'a',
            ],
            $GLOBALS['cfg']['Server']
        );

        $this->assertEquals(
            2,
            $GLOBALS['server']
        );

        // case 3
        $GLOBALS['server'] = 3;
        $this->object->user = 'testUser';
        $this->object->password = 'testPass';
        $GLOBALS['cfg']['Servers'][1] = [
            'host' => 'a',
            'user' => 'testUsers',
            'foo' => 'bar',
        ];

        $GLOBALS['cfg']['Server'] = [
            'host' => 'a',
            'user' => 'user2',
        ];

        $this->assertTrue(
            $this->object->storeCredentials()
        );

        $this->assertEquals(
            [
                'user' => 'testUser',
                'password' => 'testPass',
                'host' => 'a',
            ],
            $GLOBALS['cfg']['Server']
        );

        $this->assertEquals(
            3,
            $GLOBALS['server']
        );
    }

    /**
     * @group medium
     */
    public function testAuthFails(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue('error 123'));

        $dbi->expects($this->at(1))
            ->method('getError')
            ->will($this->returnValue('error 321'));

        $dbi->expects($this->at(2))
            ->method('getError')
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['errno'] = 31;

        ob_start();
        $this->object->showFailure('');
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            '<p>error 123</p>',
            $result
        );

        $this->object = $this->getMockBuilder(AuthenticationHttp::class)
            ->disableOriginalConstructor()
            ->setMethods(['authForm'])
            ->getMock();

        $this->object->expects($this->exactly(2))
            ->method('authForm');
        // case 2
        $GLOBALS['cfg']['Server']['host'] = 'host';
        $GLOBALS['errno'] = 1045;

        $this->object->showFailure('');

        // case 3
        $GLOBALS['errno'] = 1043;
        $this->object->showFailure('');
    }
}
