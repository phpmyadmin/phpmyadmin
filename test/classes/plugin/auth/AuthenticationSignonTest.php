<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\auth\AuthenticationSignon class
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\plugins\auth\AuthenticationSignon;

require_once 'libraries/config.default.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\auth\AuthenticationSignon class
 *
 * @package PhpMyAdmin-test
 */
class AuthenticationSignonTest extends PMATestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config;
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $this->object = new AuthenticationSignon();
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
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::auth
     *
     * @return void
     */
    public function testAuth()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = '';

        ob_start();
        $this->object->auth();
        $result = ob_get_clean();

        $this->assertContains(
            'You must set SignonURL!',
            $result
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::auth
     *
     * @return void
     */
    public function testAuthLogoutURL()
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
            ->with('Location: http://phpmyadmin.net/logoutURL' . ((SID) ? '?' . SID : ''));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);
        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'http://phpmyadmin.net/logoutURL';

        $this->object->logOut();

        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::auth
     *
     * @return void
     */
    public function testAuthLogout()
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
            ->with('Location: http://phpmyadmin.net/SignonURL' . ((SID) ? '?' . SID : ''));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);
        $GLOBALS['header'] = array();
        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $GLOBALS['cfg']['Server']['LogoutURL'] = '';

        $this->object->logOut();

        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authCheck
     *
     * @return void
     */
    public function testAuthCheckEmpty()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'http://phpmyadmin.net/SignonDiffURL';

        $this->assertFalse(
            $this->object->authCheck()
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authCheck
     *
     * @return void
     */
    public function testAuthCheckSession()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'http://phpmyadmin.net/SignonURL';
        $GLOBALS['cfg']['Server']['SignonScript'] = './examples/signon-script.php';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['port'] = '80';
        $GLOBALS['cfg']['Server']['user'] = 'user';

        $this->assertTrue(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'user',
            $GLOBALS['PHP_AUTH_USER']
        );

        $this->assertEquals(
            'password',
            $GLOBALS['PHP_AUTH_PW']
        );

        $this->assertEquals(
            'http://phpmyadmin.net/SignonURL',
            $_SESSION['LAST_SIGNON_URL']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authCheck
     *
     * @return void
     */
    public function testAuthCheckToken()
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
            ->with('Location: http://phpmyadmin.net/SignonURL' . ((SID) ? '?' . SID : ''));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['port'] = '80';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['SignonScript'] = '';
        $_COOKIE['session123'] = true;
        $_SESSION['PMA_single_signon_user'] = 'user123';
        $_SESSION['PMA_single_signon_password'] = 'pass123';
        $_SESSION['PMA_single_signon_host'] = 'local';
        $_SESSION['PMA_single_signon_port'] = '12';
        $_SESSION['PMA_single_signon_cfgupdate'] = array('foo' => 'bar');
        $_SESSION['PMA_single_signon_token'] = 'pmaToken';
        $sessionName = session_name();
        $sessionID = session_id();

        $this->object->logOut();

        $this->assertEquals(
            array(
                'SignonURL' => 'http://phpmyadmin.net/SignonURL',
                'SignonScript' => '',
                'SignonSession' => 'session123',
                'host' => 'localhost',
                'port' => '80',
                'user' => 'user',
            ),
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

        $this->assertFalse(
            isset($_SESSION['LAST_SIGNON_URL'])
        );
        $attrInstance->setValue($restoreInstance);
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authCheck
     *
     * @return void
     */
    public function testAuthCheckKeep()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
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
        $_SESSION['PMA_single_signon_cfgupdate'] = array('foo' => 'bar');
        $_SESSION['PMA_single_signon_token'] = 'pmaToken';

        $this->assertTrue(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'user123',
            $GLOBALS['PHP_AUTH_USER']
        );

        $this->assertEquals(
            'pass123',
            $GLOBALS['PHP_AUTH_PW']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authSetUser
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        $GLOBALS['PHP_AUTH_USER'] = 'testUser123';
        $GLOBALS['PHP_AUTH_PW'] = 'testPass123';

        $this->assertTrue(
            $this->object->authSetUser()
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

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authFails
     *
     * @return void
     */
    public function testAuthFailsForbidden()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('auth');

        $GLOBALS['login_without_password_is_forbidden'] = true;

        $this->object->authFails();

        $this->assertEquals(
            'Login without a password is forbidden by configuration '
            . '(see AllowNoPassword)',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authFails
     *
     * @return void
     */
    public function testAuthFailsDeny()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('auth');

        $GLOBALS['login_without_password_is_forbidden'] = null;
        $GLOBALS['allowDeny_forbidden'] = true;

        $this->object->authFails();

        $this->assertEquals(
            'Access denied!',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authFails
     *
     * @return void
     */
    public function testAuthFailsTimeout()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('auth');

        $GLOBALS['allowDeny_forbidden'] = null;
        $GLOBALS['no_activity'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = '1440';

        $this->object->authFails();

        $this->assertEquals(
            'No activity within 1440 seconds; please log in again.',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authFails
     *
     * @return void
     */
    public function testAuthFailsMySQLError()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('auth');

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue('error<123>'));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['no_activity'] = null;

        $this->object->authFails();

        $this->assertEquals(
            'error&lt;123&gt;',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authFails
     *
     * @return void
     */
    public function testAuthFailsConnect()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('auth');

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        $this->object->authFails();

        $this->assertEquals(
            'Cannot log in to the MySQL server',
            $_SESSION['PMA_single_signon_error_message']
        );
    }
}
