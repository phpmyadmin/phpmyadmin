<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\Plugins\Auth\AuthenticationCookie;
use PhpMyAdmin\Tests\AbstractNetworkTestCase;
use ReflectionException;
use ReflectionMethod;
use function base64_encode;
use function function_exists;
use function is_readable;
use function json_encode;
use function ob_get_clean;
use function ob_start;
use function str_repeat;
use function str_shuffle;
use function strlen;
use function time;

class AuthenticationCookieTest extends AbstractNetworkTestCase
{
    /** @var AuthenticationCookie */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::setTheme();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $_POST['pma_password'] = '';
        $this->object = new AuthenticationCookie();
        $GLOBALS['PMA_PHP_SELF'] = '/phpmyadmin/';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
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
     * @group medium
     */
    public function testAuthErrorAJAX(): void
    {
        $mockResponse = $this->mockResponse();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(true));

        $mockResponse->expects($this->once())
            ->method('setRequestStatus')
            ->with(false);

        $mockResponse->expects($this->once())
            ->method('addJSON')
            ->with(
                'redirect_flag',
                '1'
            );

        $GLOBALS['conn_error'] = true;
        $this->assertTrue(
            $this->object->showLoginForm()
        );
    }

    private function getAuthErrorMockResponse(): void
    {
        $mockResponse = $this->mockResponse();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        // mock footer
        $mockFooter = $this->getMockBuilder(Footer::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMinimal'])
            ->getMock();

        $mockFooter->expects($this->once())
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
                    'disableWarnings',
                ]
            )
            ->getMock();

        $mockHeader->expects($this->once())
            ->method('setBodyId')
            ->with('loginform');

        $mockHeader->expects($this->once())
            ->method('setTitle')
            ->with('phpMyAdmin');

        $mockHeader->expects($this->once())
            ->method('disableMenuAndConsole')
            ->with();

        $mockHeader->expects($this->once())
            ->method('disableWarnings')
            ->with();

        // set mocked headers and footers

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue($mockFooter));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue($mockHeader));

        $GLOBALS['cfg']['Servers'] = [
            1,
            2,
        ];

        // mock error handler

        $mockErrorHandler = $this->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasDisplayErrors'])
            ->getMock();

        $mockErrorHandler->expects($this->once())
            ->method('hasDisplayErrors')
            ->with()
            ->will($this->returnValue(true));

        $GLOBALS['error_handler'] = $mockErrorHandler;
    }

    /**
     * @group medium
     */
    public function testAuthError(): void
    {
        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = true;
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $this->object->user = 'pmauser';
        $GLOBALS['pma_auth_server'] = 'localhost';

        $GLOBALS['conn_error'] = true;
        $GLOBALS['cfg']['Lang'] = 'en';
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['db'] = 'testDb';
        $GLOBALS['table'] = 'testTable';
        $GLOBALS['cfg']['Servers'] = [1, 2];
        $GLOBALS['error_handler'] = new ErrorHandler();

        ob_start();
        $this->object->showLoginForm();
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            ' id="imLogo"',
            $result
        );

        $this->assertStringContainsString(
            '<div class="alert alert-danger" role="alert">',
            $result
        );

        $this->assertStringContainsString(
            '<form method="post" id="login_form" action="index.php?route=/" name="login_form" ' .
            'class="disableAjax hide login js-show form-horizontal">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="text" name="pma_servername" id="input_servername" ' .
            'value="localhost"',
            $result
        );

        $this->assertStringContainsString(
            '<input type="text" name="pma_username" id="input_username" ' .
            'value="pmauser" size="24" class="textfield" autocomplete="username">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="password" name="pma_password" id="input_password" ' .
            'value="" size="24" class="textfield" autocomplete="current-password">',
            $result
        );

        $this->assertStringContainsString(
            '<select name="server" id="select_server" ' .
            'onchange="document.forms[\'login_form\'].' .
            'elements[\'pma_servername\'].value = \'\'">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="db" value="testDb">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="table" value="testTable">',
            $result
        );
    }

    /**
     * @group medium
     */
    public function testAuthCaptcha(): void
    {
        $mockResponse = $this->mockResponse();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue(new Footer()));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue(new Header()));

        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = false;

        $GLOBALS['cfg']['Lang'] = '';
        $GLOBALS['cfg']['AllowArbitraryServer'] = false;
        $GLOBALS['cfg']['Servers'] = [1];
        $GLOBALS['cfg']['CaptchaApi'] = 'https://www.google.com/recaptcha/api.js';
        $GLOBALS['cfg']['CaptchaRequestParam'] = 'g-recaptcha';
        $GLOBALS['cfg']['CaptchaResponseParam'] = 'g-recaptcha-response';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $GLOBALS['server'] = 0;

        $GLOBALS['error_handler'] = new ErrorHandler();

        ob_start();
        $this->object->showLoginForm();
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString('id="imLogo"', $result);

        // Check for language selection if locales are there
        $loc = LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo';
        if (is_readable($loc)) {
            $this->assertStringContainsString(
                '<select name="lang" class="autosubmit" lang="en" dir="ltr" ' .
                'id="sel-lang">',
                $result
            );
        }

        $this->assertStringContainsString(
            '<form method="post" id="login_form" action="index.php?route=/" name="login_form"' .
            ' class="disableAjax hide login js-show form-horizontal" autocomplete="off">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="server" value="0">',
            $result
        );

        $this->assertStringContainsString(
            '<script src="https://www.google.com/recaptcha/api.js?hl=en"'
            . ' async defer></script>',
            $result
        );

        $this->assertStringContainsString(
            '<input class="btn btn-primary g-recaptcha" data-sitekey="testpubkey"'
            . ' data-callback="Functions_recaptchaCallback" value="Go" type="submit" id="input_go">',
            $result
        );
    }

    /**
     * @group medium
     */
    public function testAuthCaptchaCheckbox(): void
    {
        $mockResponse = $this->mockResponse();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue(new Footer()));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue(new Header()));

        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = false;

        $GLOBALS['cfg']['Lang'] = '';
        $GLOBALS['cfg']['AllowArbitraryServer'] = false;
        $GLOBALS['cfg']['Servers'] = [1];
        $GLOBALS['cfg']['CaptchaApi'] = 'https://www.google.com/recaptcha/api.js';
        $GLOBALS['cfg']['CaptchaRequestParam'] = 'g-recaptcha';
        $GLOBALS['cfg']['CaptchaResponseParam'] = 'g-recaptcha-response';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $GLOBALS['cfg']['CaptchaMethod'] = 'checkbox';
        $GLOBALS['server'] = 0;

        $GLOBALS['error_handler'] = new ErrorHandler();

        ob_start();
        $this->object->showLoginForm();
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString('id="imLogo"', $result);

        // Check for language selection if locales are there
        $loc = LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo';
        if (is_readable($loc)) {
            $this->assertStringContainsString(
                '<select name="lang" class="autosubmit" lang="en" dir="ltr" ' .
                'id="sel-lang">',
                $result
            );
        }

        $this->assertStringContainsString(
            '<form method="post" id="login_form" action="index.php?route=/" name="login_form"' .
            ' class="disableAjax hide login js-show form-horizontal" autocomplete="off">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="server" value="0">',
            $result
        );

        $this->assertStringContainsString(
            '<script src="https://www.google.com/recaptcha/api.js?hl=en"'
            . ' async defer></script>',
            $result
        );

        $this->assertStringContainsString(
            '<div class="g-recaptcha" data-sitekey="testpubkey"></div>',
            $result
        );

        $this->assertStringContainsString(
            '<input class="btn btn-primary" value="Go" type="submit" id="input_go">',
            $result
        );
    }

    public function testAuthHeader(): void
    {
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = false;
        $GLOBALS['cfg']['Servers'] = [1];

        $this->mockResponse('Location: https://example.com/logout');

        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logout';
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';

        $this->object->logOut();
    }

    public function testAuthHeaderPartial(): void
    {
        $GLOBALS['PMA_Config']->set('is_https', false);
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = false;
        $GLOBALS['cfg']['Servers'] = [
            1,
            2,
            3,
        ];
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logout';
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';

        $_COOKIE['pmaAuth-2'] = '';

        $this->mockResponse('Location: /phpmyadmin/index.php?route=/&server=2&lang=en');

        $this->object->logOut();
    }

    public function testAuthCheckCaptcha(): void
    {
        $GLOBALS['cfg']['CaptchaApi'] = 'https://www.google.com/recaptcha/api.js';
        $GLOBALS['cfg']['CaptchaRequestParam'] = 'g-recaptcha';
        $GLOBALS['cfg']['CaptchaResponseParam'] = 'g-recaptcha-response';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $_POST['g-recaptcha-response'] = '';
        $_POST['pma_username'] = 'testPMAUser';

        $this->assertFalse(
            $this->object->readCredentials()
        );

        $this->assertEquals(
            'Missing reCAPTCHA verification, maybe it has been blocked by adblock?',
            $GLOBALS['conn_error']
        );
    }

    public function testLogoutDelete(): void
    {
        $this->mockResponse('Location: /phpmyadmin/index.php?route=/');

        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = true;
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['PMA_Config']->set('is_https', false);
        $GLOBALS['cfg']['Servers'] = [1];

        $_COOKIE['pmaAuth-0'] = 'test';

        $this->object->logOut();

        $this->assertArrayNotHasKey(
            'pmaAuth-0',
            $_COOKIE
        );
    }

    public function testLogout(): void
    {
        $this->mockResponse('Location: /phpmyadmin/index.php?route=/');

        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = false;
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['PMA_Config']->set('is_https', false);
        $GLOBALS['cfg']['Servers'] = [1];
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server'] = ['auth_type' => 'cookie'];

        $_COOKIE['pmaAuth-1'] = 'test';

        $this->object->logOut();

        $this->assertArrayNotHasKey(
            'pmaAuth-1',
            $_COOKIE
        );
    }

    public function testAuthCheckArbitrary(): void
    {
        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $_REQUEST['old_usr'] = '';
        $_POST['pma_username'] = 'testPMAUser';
        $_REQUEST['pma_servername'] = 'testPMAServer';
        $_POST['pma_password'] = 'testPMAPSWD';
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;

        $this->assertTrue(
            $this->object->readCredentials()
        );

        $this->assertEquals(
            'testPMAUser',
            $this->object->user
        );

        $this->assertEquals(
            'testPMAPSWD',
            $this->object->password
        );

        $this->assertEquals(
            'testPMAServer',
            $GLOBALS['pma_auth_server']
        );

        $this->assertArrayNotHasKey(
            'pmaAuth-1',
            $_COOKIE
        );
    }

    public function testAuthCheckInvalidCookie(): void
    {
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $_REQUEST['pma_servername'] = 'testPMAServer';
        $_POST['pma_password'] = 'testPMAPSWD';
        $_POST['pma_username'] = '';
        $GLOBALS['server'] = 1;
        $_COOKIE['pmaUser-1'] = '';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');

        $this->assertFalse(
            $this->object->readCredentials()
        );
    }

    public function testAuthCheckExpires(): void
    {
        $GLOBALS['server'] = 1;
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $_COOKIE['pmaAuth-1'] = '';
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = time() - 1000;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;

        $this->assertFalse(
            $this->object->readCredentials()
        );
    }

    public function testAuthCheckDecryptUser(): void
    {
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_POST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = '';
        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['PMA_Config']->set('is_https', false);

        // mock for blowfish function
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['cookieDecrypt'])
            ->getMock();

        $this->object->expects($this->once())
            ->method('cookieDecrypt')
            ->will($this->returnValue('testBF'));

        $this->assertFalse(
            $this->object->readCredentials()
        );

        $this->assertEquals(
            'testBF',
            $this->object->user
        );
    }

    public function testAuthCheckDecryptPassword(): void
    {
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_POST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pmaAuth-1'] = 'pmaAuth1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $_SESSION['browser_access_time']['default'] = time() - 1000;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;
        $GLOBALS['PMA_Config']->set('is_https', false);

        // mock for blowfish function
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['cookieDecrypt'])
            ->getMock();

        $this->object->expects($this->at(1))
            ->method('cookieDecrypt')
            ->will($this->returnValue('{"password":""}'));

        $this->assertTrue(
            $this->object->readCredentials()
        );

        $this->assertTrue(
            $GLOBALS['from_cookie']
        );

        $this->assertEquals(
            '',
            $this->object->password
        );
    }

    public function testAuthCheckAuthFails(): void
    {
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_POST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = 1;
        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['LoginCookieValidity'] = 0;
        $_SESSION['browser_access_time']['default'] = -1;
        $GLOBALS['PMA_Config']->set('is_https', false);

        // mock for blowfish function
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['showFailure', 'cookieDecrypt'])
            ->getMock();

        $this->object->expects($this->once())
            ->method('cookieDecrypt')
            ->will($this->returnValue('testBF'));

        $this->object->expects($this->once())
            ->method('showFailure');

        $this->assertFalse(
            $this->object->readCredentials()
        );
    }

    public function testAuthSetUser(): void
    {
        $this->object->user = 'pmaUser2';
        $arr = [
            'host' => 'a',
            'port' => 1,
            'socket' => true,
            'ssl' => true,
            'user' => 'pmaUser2',
        ];

        $GLOBALS['cfg']['Server'] = $arr;
        $GLOBALS['cfg']['Server']['user'] = 'pmaUser';
        $GLOBALS['cfg']['Servers'][1] = $arr;
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $this->object->password = 'testPW';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['LoginCookieStore'] = true;
        $GLOBALS['from_cookie'] = true;
        $GLOBALS['PMA_Config']->set('is_https', false);

        $this->object->storeCredentials();

        $this->object->rememberCredentials();

        $this->assertArrayHasKey(
            'pmaUser-2',
            $_COOKIE
        );

        $this->assertArrayHasKey(
            'pmaAuth-2',
            $_COOKIE
        );

        $arr['password'] = 'testPW';
        $arr['host'] = 'b';
        $arr['port'] = '2';
        $this->assertEquals(
            $arr,
            $GLOBALS['cfg']['Server']
        );
    }

    public function testAuthSetUserWithHeaders(): void
    {
        $this->object->user = 'pmaUser2';
        $arr = [
            'host' => 'a',
            'port' => 1,
            'socket' => true,
            'ssl' => true,
            'user' => 'pmaUser2',
        ];

        $GLOBALS['cfg']['Server'] = $arr;
        $GLOBALS['cfg']['Server']['host'] = 'b';
        $GLOBALS['cfg']['Server']['user'] = 'pmaUser';
        $GLOBALS['cfg']['Servers'][1] = $arr;
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $this->object->password = 'testPW';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['LoginCookieStore'] = true;
        $GLOBALS['from_cookie'] = false;

        $this->mockResponse(
            $this->stringContains('&server=2&lang=en')
        );

        $this->object->storeCredentials();
        $this->object->rememberCredentials();
    }

    public function testAuthFailsNoPass(): void
    {
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        $this->mockResponse(
            ['Cache-Control: no-store, no-cache, must-revalidate'],
            ['Pragma: no-cache']
        );
        $this->object->showFailure('empty-denied');

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Login without a password is forbidden by configuration'
            . ' (see AllowNoPassword)'
        );
    }

    public function dataProviderPasswordLength(): array
    {
        return [
            [
                str_repeat('a', 1000),
                false,
                'Your password is too long. To prevent denial-of-service attacks,'
                . ' phpMyAdmin restricts passwords to less than 1000 characters.',
            ],
            [
                str_repeat('a', 1001),
                false,
                'Your password is too long. To prevent denial-of-service attacks,'
                . ' phpMyAdmin restricts passwords to less than 1000 characters.',
            ],
            [
                str_repeat('a', 3000),
                false,
                'Your password is too long. To prevent denial-of-service attacks,'
                . ' phpMyAdmin restricts passwords to less than 1000 characters.',
            ],
            [
                str_repeat('a', 256),
                true,
                null,
            ],
            [
                '',
                true,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderPasswordLength
     */
    public function testAuthFailsTooLongPass(string $password, bool $trueFalse, ?string $connError): void
    {
        $_POST['pma_username'] = str_shuffle('123456987rootfoobar');
        $_POST['pma_password'] = $password;

        if ($trueFalse === false) {
            $this->assertFalse(
                $this->object->readCredentials()
            );
        } else {
            $this->assertTrue(
                $this->object->readCredentials()
            );
        }

        $this->assertEquals(
            $GLOBALS['conn_error'],
            $connError
        );
    }

    public function testAuthFailsDeny(): void
    {
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        $this->mockResponse(
            ['Cache-Control: no-store, no-cache, must-revalidate'],
            ['Pragma: no-cache']
        );
        $this->object->showFailure('allow-denied');

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Access denied!'
        );
    }

    public function testAuthFailsActivity(): void
    {
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        $GLOBALS['allowDeny_forbidden'] = '';
        $GLOBALS['cfg']['LoginCookieValidity'] = 10;

        $this->mockResponse(
            ['Cache-Control: no-store, no-cache, must-revalidate'],
            ['Pragma: no-cache']
        );
        $this->object->showFailure('no-activity');

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'You have been automatically logged out due to inactivity of 10 seconds.'
            . ' Once you log in again, you should be able to resume the work where you left off.'
        );
    }

    public function testAuthFailsDBI(): void
    {
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['errno'] = 42;

        $this->mockResponse(
            ['Cache-Control: no-store, no-cache, must-revalidate'],
            ['Pragma: no-cache']
        );
        $this->object->showFailure('');

        $this->assertEquals(
            $GLOBALS['conn_error'],
            '#42 Cannot log in to the MySQL server'
        );
    }

    public function testAuthFailsErrno(): void
    {
        $this->object = $this->getMockBuilder(AuthenticationCookie::class)
            ->disableOriginalConstructor()
            ->setMethods(['showLoginForm'])
            ->getMock();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        unset($GLOBALS['errno']);

        $this->mockResponse(
            ['Cache-Control: no-store, no-cache, must-revalidate'],
            ['Pragma: no-cache']
        );
        $this->object->showFailure('');

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Cannot log in to the MySQL server'
        );
    }

    public function testGetEncryptionSecretEmpty(): void
    {
        $method = new ReflectionMethod(
            AuthenticationCookie::class,
            'getEncryptionSecret'
        );
        $method->setAccessible(true);

        $GLOBALS['cfg']['blowfish_secret'] = '';
        $_SESSION['encryption_key'] = '';

        $result = $method->invoke($this->object, null);

        $this->assertEquals(
            $result,
            $_SESSION['encryption_key']
        );

        $this->assertEquals(
            32,
            strlen($result)
        );
    }

    public function testGetEncryptionSecretConfigured(): void
    {
        $method = new ReflectionMethod(
            AuthenticationCookie::class,
            'getEncryptionSecret'
        );
        $method->setAccessible(true);

        $GLOBALS['cfg']['blowfish_secret'] = 'notEmpty';

        $result = $method->invoke($this->object, null);

        $this->assertEquals(
            'notEmpty',
            $result
        );
    }

    public function testCookieEncrypt(): void
    {
        $this->object->setIV('testiv09testiv09');
        // works with the openssl extension active or inactive
        $this->assertEquals(
            '{"iv":"dGVzdGl2MDl0ZXN0aXYwOQ==","mac":"347aa45ae1ade00c980f31129ec2def'
            . 'ef18b2bfd","payload":"YDEaxOfP9nD9q\/2pC6hjfQ=="}',
            $this->object->cookieEncrypt('data123', 'sec321')
        );
    }

    public function testCookieEncryptPHPSecLib(): void
    {
        $this->object->setUseOpenSSL(false);
        $this->testCookieEncrypt();
    }

    public function testCookieEncryptOpenSSL(): void
    {
        if (! function_exists('openssl_encrypt')) {
            $this->markTestSkipped('openssl not available');
        }
        $this->object->setUseOpenSSL(true);
        $this->testCookieEncrypt();
    }

    public function testCookieDecrypt(): void
    {
        // works with the openssl extension active or inactive
        $this->assertEquals(
            'data123',
            $this->object->cookieDecrypt(
                '{"iv":"dGVzdGl2MDl0ZXN0aXYwOQ==","mac":"347aa45ae1ade00c980f31129ec'
                . '2defef18b2bfd","payload":"YDEaxOfP9nD9q\/2pC6hjfQ=="}',
                'sec321'
            )
        );
        $this->assertEquals(
            'root',
            $this->object->cookieDecrypt(
                '{"iv":"AclJhCM7ryNiuPnw3Y8cXg==","mac":"d0ef75e852bc162e81496e116dc'
                . '571182cb2cba6","payload":"O4vrt9R1xyzAw7ypvrLmQA=="}',
                ':Kb1?)c(r{]-{`HW*hOzuufloK(M~!p'
            )
        );
        $this->assertFalse(
            $this->object->cookieDecrypt(
                '{"iv":"AclJhCM7ryNiuPnw3Y8cXg==","mac":"d0ef75e852bc162e81496e116dc'
                . '571182cb2cba6","payload":"O4vrt9R1xyzAw7ypvrLmQA=="}',
                'aedzoiefpzf,zf1z7ef6ef84'
            )
        );
    }

    public function testCookieDecryptPHPSecLib(): void
    {
        $this->object->setUseOpenSSL(false);
        $this->testCookieDecrypt();
    }

    public function testCookieDecryptOpenSSL(): void
    {
        if (! function_exists('openssl_encrypt')) {
            $this->markTestSkipped('openssl not available');
        }
        $this->object->setUseOpenSSL(true);
        $this->testCookieDecrypt();
    }

    public function testCookieDecryptInvalid(): void
    {
        // works with the openssl extension active or inactive
        $this->assertFalse(
            $this->object->cookieDecrypt(
                '{"iv":0,"mac":0,"payload":0}',
                'sec321'
            )
        );
    }

    /**
     * Test for secret splitting using getAESSecret
     *
     * @param string $secret secret
     * @param string $mac    mac
     * @param string $aes    aes
     *
     * @dataProvider secretsProvider
     */
    public function testMACSecretSplit(string $secret, string $mac, string $aes): void
    {
        $this->assertNotEmpty($aes);// Useless check
        $this->assertEquals(
            $mac,
            $this->object->getMACSecret($secret)
        );
    }

    /**
     * Test for secret splitting using getMACSecret and getAESSecret
     *
     * @param string $secret secret
     * @param string $mac    mac
     * @param string $aes    aes
     *
     * @dataProvider secretsProvider
     */
    public function testAESSecretSplit(string $secret, string $mac, string $aes): void
    {
        $this->assertNotEmpty($mac);// Useless check
        $this->assertEquals(
            $aes,
            $this->object->getAESSecret($secret)
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testPasswordChange(): void
    {
        $newPassword = 'PMAPASSWD2';
        $GLOBALS['PMA_Config']->set('is_https', false);
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $_SESSION['encryption_key'] = '';
        $this->object->setIV('testiv09testiv09');

        $this->object->handlePasswordChange($newPassword);

        $payload = [
            'password' => $newPassword,
            'server' => 'b 2',
        ];
        $method = new ReflectionMethod(
            AuthenticationCookie::class,
            'getSessionEncryptionSecret'
        );
        $method->setAccessible(true);

        $encryptedCookie = $this->object->cookieEncrypt(
            (string) json_encode($payload),
            $method->invoke($this->object, null)
        );
        $this->assertEquals(
            $_COOKIE['pmaAuth-' . $GLOBALS['server']],
            $encryptedCookie
        );
    }

    /**
     * Data provider for secrets splitting.
     *
     * @return array
     */
    public function secretsProvider(): array
    {
        return [
            // Optimal case
            [
                '1234567890123456abcdefghijklmnop',
                '1234567890123456',
                'abcdefghijklmnop',
            ],
            // Overlapping secret
            [
                '12345678901234567',
                '1234567890123456',
                '2345678901234567',
            ],
            // Short secret
            [
                '1234567890123456',
                '1234567890123451',
                '2345678901234562',
            ],
            // Really short secret
            [
                '12',
                '1111111111111111',
                '2222222222222222',
            ],
            // Too short secret
            [
                '1',
                '1111111111111111',
                '1111111111111111',
            ],
        ];
    }

    public function testAuthenticate(): void
    {
        $GLOBALS['cfg']['CaptchaApi'] = '';
        $GLOBALS['cfg']['CaptchaRequestParam'] = '';
        $GLOBALS['cfg']['CaptchaResponseParam'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['Server']['AllowRoot'] = false;
        $GLOBALS['cfg']['Server']['AllowNoPassword'] = false;
        $_REQUEST['old_usr'] = '';
        $_POST['pma_username'] = 'testUser';
        $_POST['pma_password'] = 'testPassword';

        ob_start();
        $this->object->authenticate();
        $result = ob_get_clean();

        /* Nothing should be printed */
        $this->assertEquals('', $result);

        /* Verify readCredentials worked */
        $this->assertEquals('testUser', $this->object->user);
        $this->assertEquals('testPassword', $this->object->password);

        /* Verify storeCredentials worked */
        $this->assertEquals('testUser', $GLOBALS['cfg']['Server']['user']);
        $this->assertEquals('testPassword', $GLOBALS['cfg']['Server']['password']);
    }

    /**
     * @param string $user     user
     * @param string $pass     pass
     * @param string $ip       ip
     * @param bool   $root     root
     * @param bool   $nopass   nopass
     * @param array  $rules    rules
     * @param string $expected expected result
     *
     * @dataProvider checkRulesProvider
     */
    public function testCheckRules(
        string $user,
        string $pass,
        string $ip,
        bool $root,
        bool $nopass,
        array $rules,
        string $expected
    ): void {
        $this->object->user = $user;
        $this->object->password = $pass;
        $this->object->storeCredentials();

        $_SERVER['REMOTE_ADDR'] = $ip;

        $GLOBALS['cfg']['Server']['AllowRoot'] = $root;
        $GLOBALS['cfg']['Server']['AllowNoPassword'] = $nopass;
        $GLOBALS['cfg']['Server']['AllowDeny'] = $rules;

        if (! empty($expected)) {
            $this->getAuthErrorMockResponse();
        }

        ob_start();
        $this->object->checkRules();
        $result = ob_get_clean();

        $this->assertIsString($result);

        if (empty($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertStringContainsString($expected, $result);
        }
    }

    public function checkRulesProvider(): array
    {
        return [
            'nopass-ok' => [
                'testUser',
                '',
                '1.2.3.4',
                true,
                true,
                [],
                '',
            ],
            'nopass' => [
                'testUser',
                '',
                '1.2.3.4',
                true,
                false,
                [],
                'Login without a password is forbidden',
            ],
            'root-ok' => [
                'root',
                'root',
                '1.2.3.4',
                true,
                true,
                [],
                '',
            ],
            'root' => [
                'root',
                'root',
                '1.2.3.4',
                false,
                true,
                [],
                'Access denied!',
            ],
            'rules-deny-allow-ok' => [
                'root',
                'root',
                '1.2.3.4',
                true,
                true,
                [
                    'order' => 'deny,allow',
                    'rules' => [
                        'allow root 1.2.3.4',
                        'deny % from all',
                    ],
                ],
                '',
            ],
            'rules-deny-allow-reject' => [
                'user',
                'root',
                '1.2.3.4',
                true,
                true,
                [
                    'order' => 'deny,allow',
                    'rules' => [
                        'allow root 1.2.3.4',
                        'deny % from all',
                    ],
                ],
                'Access denied!',
            ],
            'rules-allow-deny-ok' => [
                'root',
                'root',
                '1.2.3.4',
                true,
                true,
                [
                    'order' => 'allow,deny',
                    'rules' => [
                        'deny user from all',
                        'allow root 1.2.3.4',
                    ],
                ],
                '',
            ],
            'rules-allow-deny-reject' => [
                'user',
                'root',
                '1.2.3.4',
                true,
                true,
                [
                    'order' => 'allow,deny',
                    'rules' => [
                        'deny user from all',
                        'allow root 1.2.3.4',
                    ],
                ],
                'Access denied!',
            ],
            'rules-explicit-ok' => [
                'root',
                'root',
                '1.2.3.4',
                true,
                true,
                [
                    'order' => 'explicit',
                    'rules' => [
                        'deny user from all',
                        'allow root 1.2.3.4',
                    ],
                ],
                '',
            ],
            'rules-explicit-reject' => [
                'user',
                'root',
                '1.2.3.4',
                true,
                true,
                [
                    'order' => 'explicit',
                    'rules' => [
                        'deny user from all',
                        'allow root 1.2.3.4',
                    ],
                ],
                'Access denied!',
            ],
        ];
    }
}
