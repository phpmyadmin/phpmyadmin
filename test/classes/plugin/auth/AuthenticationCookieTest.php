<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\auth\AuthenticationCookie class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\plugins\auth\AuthenticationCookie;
use PMA\libraries\Theme;

$GLOBALS['PMA_Config'] = new PMA\libraries\Config();

require_once 'libraries/config.default.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/plugins/auth/AuthenticationCookie.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\auth\AuthenticationCookie class
 *
 * @package PhpMyAdmin-test
 */
class AuthenticationCookieTest extends PMATestCase
{
    /**
     * @var AuthenticationCookie
     */
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $this->object = new AuthenticationCookie();

        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['PMA_PHP_SELF'] = '/phpmyadmin/';
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::auth
     *
     * @return void
     * @group medium
     */
    public function testAuthErrorAJAX()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'setRequestStatus', 'addJSON'))
            ->getMock();

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

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);
        $GLOBALS['conn_error'] = true;
        $this->assertTrue(
            $this->object->auth()
        );

        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::auth
     *
     * @return void
     * @group medium
     */
    public function testAuthError()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();
        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'getFooter', 'getHeader'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = true;
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $GLOBALS['PHP_AUTH_USER'] = 'pmauser';
        $GLOBALS['pma_auth_server'] = 'localhost';

        // mock footer
        $mockFooter = $this->getMockBuilder('PMA\libraries\Footer')
            ->disableOriginalConstructor()
            ->setMethods(array('setMinimal'))
            ->getMock();

        $mockFooter->expects($this->once())
            ->method('setMinimal')
            ->with();

        // mock header

        $mockHeader = $this->getMockBuilder('PMA\libraries\Header')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
                    'setBodyId',
                    'setTitle',
                    'disableMenuAndConsole',
                    'disableWarnings'
                )
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

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $GLOBALS['pmaThemeImage'] = 'test';
        $GLOBALS['conn_error'] = true;
        $GLOBALS['cfg']['Lang'] = 'en';
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['cfg']['Servers'] = array(1, 2);
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['target'] = 'testTarget';
        $GLOBALS['db'] = 'testDb';
        $GLOBALS['table'] = 'testTable';

        file_put_contents('testlogo_right.png', '');

        // mock error handler

        $mockErrorHandler = $this->getMockBuilder('PMA\libraries\ErrorHandler')
            ->disableOriginalConstructor()
            ->setMethods(array('hasDisplayErrors', 'dispErrors'))
            ->getMock();

        $mockErrorHandler->expects($this->once())
            ->method('hasDisplayErrors')
            ->with()
            ->will($this->returnValue(true));

        $mockErrorHandler->expects($this->once())
            ->method('dispErrors')
            ->with();

        $GLOBALS['error_handler'] = $mockErrorHandler;

        ob_start();
        $this->object->auth();
        $result = ob_get_clean();

        // assertions

        $this->assertContains(
            '<img src="testlogo_right.png" id="imLogo"',
            $result
        );

        $this->assertContains(
            '<div class="error">',
            $result
        );

        $this->assertContains(
            '<form method="post" action="index.php" name="login_form" ' .
            'class="disableAjax login hide js-show">',
            $result
        );

        $this->assertContains(
            '<input type="text" name="pma_servername" id="input_servername" ' .
            'value="localhost"',
            $result
        );

        $this->assertContains(
            '<input type="text" name="pma_username" id="input_username" ' .
            'value="pmauser" size="24" class="textfield"/>',
            $result
        );

        $this->assertContains(
            '<input type="password" name="pma_password" id="input_password" ' .
            'value="" size="24" class="textfield" />',
            $result
        );

        $this->assertContains(
            '<select name="server" id="select_server" ' .
            'onchange="document.forms[\'login_form\'].' .
            'elements[\'pma_servername\'].value = \'\'" >',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="target" value="testTarget" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="db" value="testDb" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="table" value="testTable" />',
            $result
        );

        @unlink('testlogo_right.png');
        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::auth
     *
     * @return void
     * @group medium
     */
    public function testAuthCaptcha()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();
        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'getFooter', 'getHeader'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue(new PMA\libraries\Footer()));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue(new PMA\libraries\Header()));

        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = false;

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $GLOBALS['pmaThemeImage'] = 'test';
        $GLOBALS['cfg']['Lang'] = '';
        $GLOBALS['cfg']['AllowArbitraryServer'] = false;
        $GLOBALS['cfg']['Servers'] = array(1);
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $GLOBALS['server'] = 0;

        $GLOBALS['error_handler'] = new PMA\libraries\ErrorHandler;

        ob_start();
        $this->object->auth();
        $result = ob_get_clean();

        // assertions

        $this->assertContains(
            '<img name="imLogo" id="imLogo" src="testpma_logo.png"',
            $result
        );

        $this->assertContains(
            '<select name="lang" class="autosubmit" lang="en" dir="ltr" ' .
            'id="sel-lang">',
            $result
        );

        $this->assertContains(
            '<form method="post" action="index.php" name="login_form" ' .
            'autocomplete="off" class="disableAjax login hide js-show">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="server" value="0" />',
            $result
        );

        $this->assertContains(
            '<script src="https://www.google.com/recaptcha/api.js?hl=en"'
            . ' async defer></script>',
            $result
        );

        $this->assertContains(
            '<div class="g-recaptcha" data-sitekey="testpubkey">',
            $result
        );

        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::auth with headers
     *
     * @return void
     */
    public function testAuthHeader()
    {
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = false;
        $GLOBALS['cfg']['Servers'] = array(1);

        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'headersSent', 'header'))
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('header')
            ->with('Location: https://example.com/logout');

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logout';

        $this->object->logOut();

        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testAuthCheckCaptcha()
    {
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $_POST["g-recaptcha-response"] = '';
        $_REQUEST['pma_username'] = 'testPMAUser';

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'Please enter correct captcha!',
            $GLOBALS['conn_error']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testLogoutDelete()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'headersSent', 'header'))
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('header')
            ->with('Location: /phpmyadmin/index.php');

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = true;
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['cfg']['Servers'] = array(1);

        $_COOKIE['pmaAuth-0'] = 'test';

        $this->object->logOut();

        $this->assertFalse(
            isset($_COOKIE['pmaAuth-0'])
        );
        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testLogout()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'headersSent', 'header'))
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('header')
            ->with('Location: /phpmyadmin/index.php');

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = false;
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['cfg']['Servers'] = array(1);
        $GLOBALS['server'] = 1;

        $_COOKIE['pmaAuth-1'] = 'test';

        $this->object->logOut();

        $this->assertFalse(
            isset($_COOKIE['pmaAuth-1'])
        );
        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testAuthCheckArbitrary()
    {
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = 'testPMAUser';
        $_REQUEST['pma_servername'] = 'testPMAServer';
        $_REQUEST['pma_password'] = 'testPMAPSWD';
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;

        $this->assertTrue(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'testPMAUser',
            $GLOBALS['PHP_AUTH_USER']
        );

        $this->assertEquals(
            'testPMAPSWD',
            $GLOBALS['PHP_AUTH_PW']
        );

        $this->assertEquals(
            'testPMAServer',
            $GLOBALS['pma_auth_server']
        );

        $this->assertFalse(
            isset($_COOKIE['pmaAuth-1'])
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testAuthCheckInvalidCookie()
    {
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $_REQUEST['pma_servername'] = 'testPMAServer';
        $_REQUEST['pma_password'] = 'testPMAPSWD';
        $_REQUEST['pma_username'] = '';
        $GLOBALS['server'] = 1;
        $_COOKIE['pmaUser-1'] = '';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');

        $this->assertFalse(
            $this->object->authCheck()
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testAuthCheckExpires()
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
            $this->object->authCheck()
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck (mock blowfish functions reqd)
     *
     * @return void
     */
    public function testAuthCheckDecryptUser()
    {
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = '';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';

        // mock for blowfish function
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('cookieDecrypt'))
            ->getMock();

        $this->object->expects($this->once())
            ->method('cookieDecrypt')
            ->will($this->returnValue('testBF'));

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'testBF',
            $GLOBALS['PHP_AUTH_USER']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck (mocking blowfish functions)
     *
     * @return void
     */
    public function testAuthCheckDecryptPassword()
    {
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pmaAuth-1'] = 'pmaAuth1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $_SESSION['last_access_time'] = time() - 1000;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;

        // mock for blowfish function
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('cookieDecrypt'))
            ->getMock();

        $this->object->expects($this->at(1))
            ->method('cookieDecrypt')
            ->will($this->returnValue('{"password":""}'));

        $this->assertTrue(
            $this->object->authCheck()
        );

        $this->assertTrue(
            $GLOBALS['from_cookie']
        );

        $this->assertEquals(
            '',
            $GLOBALS['PHP_AUTH_PW']
        );

    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authCheck (mocking the object itself)
     *
     * @return void
     */
    public function testAuthCheckAuthFails()
    {
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pma_iv-1'] = base64_encode('testiv09testiv09');
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = 1;
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $GLOBALS['cfg']['LoginCookieValidity'] = 0;
        $_SESSION['last_access_time'] = -1;
        // mock for blowfish function
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('authFails'))
            ->getMock();

        $this->object->expects($this->once())
            ->method('authFails');

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertTrue(
            $GLOBALS['no_activity']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authSetUser
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        $GLOBALS['PHP_AUTH_USER'] = 'pmaUser2';
        $arr = array(
            'host' => 'a',
            'port' => 1,
            'socket' => true,
            'ssl' => true,
            'connect_type' => 'port',
            'user' => 'pmaUser2'
        );

        $GLOBALS['cfg']['Server'] = $arr;
        $GLOBALS['cfg']['Server']['user'] = 'pmaUser';
        $GLOBALS['cfg']['Servers'][1] = $arr;
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $GLOBALS['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'] = 'testPW';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['LoginCookieStore'] = true;
        $GLOBALS['from_cookie'] = true;

        $this->object->authSetUser();

        $this->assertFalse(
            isset($GLOBALS['PHP_AUTH_PW'])
        );

        $this->assertFalse(
            isset($_SERVER['PHP_AUTH_PW'])
        );

        $this->object->storeUserCredentials();

        $this->assertTrue(
            isset($_COOKIE['pmaUser-1'])
        );

        $this->assertTrue(
            isset($_COOKIE['pmaAuth-1'])
        );

        $arr['password'] = 'testPW';
        $arr['host'] = 'b';
        $arr['port'] = '2';
        $this->assertEquals(
            $arr,
            $GLOBALS['cfg']['Server']
        );

    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authSetUser (check for headers redirect)
     *
     * @return void
     */
    public function testAuthSetUserWithHeaders()
    {
        $GLOBALS['PHP_AUTH_USER'] = 'pmaUser2';
        $arr = array(
            'host' => 'a',
            'port' => 1,
            'socket' => true,
            'ssl' => true,
            'connect_type' => 'port',
            'user' => 'pmaUser2'
        );

        $GLOBALS['cfg']['Server'] = $arr;
        $GLOBALS['cfg']['Server']['host'] = 'b';
        $GLOBALS['cfg']['Server']['user'] = 'pmaUser';
        $GLOBALS['cfg']['Servers'][1] = $arr;
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $GLOBALS['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'] = 'testPW';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['LoginCookieStore'] = true;
        $GLOBALS['from_cookie'] = false;
        $GLOBALS['collation_connection'] = 'utf-8';

        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('disable', 'header', 'headersSent'))
            ->getMock();

        $mockResponse->expects($this->at(0))
            ->method('disable');

        // target can be "phpunit" or "ide-phpunit.php",
        // depending on testing environment
        $mockResponse->expects($this->once())
            ->method('header')
            ->with(
                $this->stringContains('&server=2&lang=en&collation_connection=utf-8&token=token')
            );

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $this->object->authSetUser();
        $this->object->storeUserCredentials();

        $attrInstance->setValue($restoreInstance);
    }

    public function doMockResponse()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();

        // set mocked headers and footers
        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('header', 'headersSent'))
            ->getMock();

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $headers = func_get_args();

        $header_method = $mockResponse->expects($this->exactly(count($headers)))
            ->method('header');

        call_user_func_array(array($header_method, 'withConsecutive'), $headers);

        $this->object->authFails();

        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::authFails
     *
     * @return void
     */
    public function testAuthFailsNoPass()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        $GLOBALS['login_without_password_is_forbidden'] = '1';

        $this->doMockResponse(
            array('Cache-Control: no-store, no-cache, must-revalidate'),
            array('Pragma: no-cache')
        );

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Login without a password is forbidden by configuration'
            . ' (see AllowNoPassword)'
        );

    }

    public function testAuthFailsDeny()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        $GLOBALS['login_without_password_is_forbidden'] = '';
        $GLOBALS['allowDeny_forbidden'] = '1';

        $this->doMockResponse(
            array('Cache-Control: no-store, no-cache, must-revalidate'),
            array('Pragma: no-cache')
        );

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Access denied!'
        );
    }

    public function testAuthFailsActivity()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';


        $GLOBALS['allowDeny_forbidden'] = '';
        $GLOBALS['no_activity'] = '1';
        $GLOBALS['cfg']['LoginCookieValidity'] = 10;

        $this->doMockResponse(
            array('Cache-Control: no-store, no-cache, must-revalidate'),
            array('Pragma: no-cache')
        );

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'No activity within 10 seconds; please log in again.'
        );
    }

    public function testAuthFailsDBI()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';


        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['no_activity'] = '';
        $GLOBALS['errno'] = 42;

        $this->doMockResponse(
            array('Cache-Control: no-store, no-cache, must-revalidate'),
            array('Pragma: no-cache')
        );

        $this->assertEquals(
            $GLOBALS['conn_error'],
            '#42 Cannot log in to the MySQL server'
        );
    }

    public function testAuthFailsErrno()
    {
        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['server'] = 2;
        $_COOKIE['pmaAuth-2'] = 'pass';

        unset($GLOBALS['errno']);

        $this->doMockResponse(
            array('Cache-Control: no-store, no-cache, must-revalidate'),
            array('Pragma: no-cache')
        );

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Cannot log in to the MySQL server'
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::_getEncryptionSecret
     *
     * @return void
     */
    public function testGetEncryptionSecretEmpty()
    {
        $method = new \ReflectionMethod(
            'PMA\libraries\plugins\auth\AuthenticationCookie',
            '_getEncryptionSecret'
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

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::_getEncryptionSecret
     *
     * @return void
     */
    public function testGetEncryptionSecretConfigured()
    {
        $method = new \ReflectionMethod(
            'PMA\libraries\plugins\auth\AuthenticationCookie',
            '_getEncryptionSecret'
        );
        $method->setAccessible(true);

        $GLOBALS['cfg']['blowfish_secret'] = 'notEmpty';

        $result = $method->invoke($this->object, null);

        $this->assertEquals(
            'notEmpty',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::cookieEncrypt
     *
     * @return void
     */
    public function testCookieEncrypt()
    {
        $this->object->setIV('testiv09testiv09');
        // works with the openssl extension active or inactive
        $this->assertEquals(
            '{"iv":"dGVzdGl2MDl0ZXN0aXYwOQ==","mac":"34367a80e4276906637b5eaecf8c3931547aae68","payload":"+coP\/up\/ZBTBwbiEpCUVXQ=="}',
            $this->object->cookieEncrypt('data123', 'sec321')
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::cookieDecrypt
     *
     * @return void
     */
    public function testCookieDecrypt()
    {
        // works with the openssl extension active or inactive
        $this->assertEquals(
            'data123',
            $this->object->cookieDecrypt(
                '{"iv":"dGVzdGl2MDl0ZXN0aXYwOQ==","mac":"34367a80e4276906637b5eaecf8c3931547aae68","payload":"+coP\/up\/ZBTBwbiEpCUVXQ=="}',
                'sec321'
            )
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationConfig::cookieDecrypt
     *
     * @return void
     */
    public function testCookieDecryptInvalid()
    {
        // works with the openssl extension active or inactive
        $this->assertEquals(
            false,
            $this->object->cookieDecrypt(
                '{"iv":0,"mac":0,"payload":0}',
                'sec321'
            )
        );
    }

    /**
     * Test for secret splitting using getAESSecret
     *
     * @return void
     *
     * @dataProvider secretsProvider
     */
    public function testMACSecretSplit($secret, $mac, $aes)
    {
        $this->assertEquals(
            $mac,
            $this->object->getMACSecret($secret)
        );
    }

    /**
     * Test for secret splitting using getMACSecret and getAESSecret
     *
     * @return void
     *
     * @dataProvider secretsProvider
     */
    public function testAESSecretSplit($secret, $mac, $aes)
    {
        $this->assertEquals(
            $aes,
            $this->object->getAESSecret($secret)
        );
    }

    /**
     * Data provider for secrets splitting.
     *
     * @return array
     */
    public function secretsProvider()
    {
        return array(
            // Optimal case
            array(
                '1234567890123456abcdefghijklmnop',
                '1234567890123456',
                'abcdefghijklmnop',
            ),
            // Overlapping secret
            array(
                '12345678901234567',
                '1234567890123456',
                '2345678901234567',
            ),
            // Short secret
            array(
                '1234567890123456',
                '1234567890123451',
                '2345678901234562',
            ),
            // Really short secret
            array(
                '12',
                '1111111111111111',
                '2222222222222222',
            ),
            // Too short secret
            array(
                '1',
                '1111111111111111',
                '1111111111111111',
            ),
        );
    }
}
