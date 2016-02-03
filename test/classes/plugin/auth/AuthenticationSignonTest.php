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
        if (! defined('PMA_TEST_HEADERS')) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        // case 1

        $GLOBALS['cfg']['Server']['SignonURL'] = '';

        ob_start();
        $this->object->auth();
        $result = ob_get_clean();

        $this->assertContains(
            'You must set SignonURL!',
            $result
        );

        // case 2

        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $_REQUEST['old_usr'] = 'oldUser';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'http://phpmyadmin.net/logoutURL';

        $this->object->auth();

        $this->assertContains(
            'Location: http://phpmyadmin.net/logoutURL?PHPSESSID=',
            $GLOBALS['header'][0]
        );

        // case 3

        $GLOBALS['header'] = array();
        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['Server']['LogoutURL'] = '';

        $this->object->auth();

        $this->assertContains(
            'Location: http://phpmyadmin.net/SignonURL?PHPSESSID=',
            $GLOBALS['header'][0]
        );
    }

    /**
     * Test for PMA\libraries\plugins\auth\AuthenticationSignon::authCheck
     *
     * @return void
     */
    public function testAuthCheck()
    {
        // case 1

        $GLOBALS['cfg']['Server']['SignonURL'] = 'http://phpmyadmin.net/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'http://phpmyadmin.net/SignonDiffURL';

        $this->assertFalse(
            $this->object->authCheck()
        );

        // case 2

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

        // case 3

        $GLOBALS['cfg']['Server']['SignonScript'] = '';
        $_COOKIE['session123'] = true;
        $_REQUEST['old_usr'] = 'oldUser';
        $_SESSION['PMA_single_signon_user'] = 'user123';
        $_SESSION['PMA_single_signon_password'] = 'pass123';
        $_SESSION['PMA_single_signon_host'] = 'local';
        $_SESSION['PMA_single_signon_port'] = '12';
        $_SESSION['PMA_single_signon_cfgupdate'] = array('foo' => 'bar');
        $_SESSION['PMA_single_signon_token'] = 'pmaToken';
        $sessionName = session_name();
        $sessionID = session_id();

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertEquals(
            array(
                'SignonURL' => 'http://phpmyadmin.net/SignonURL',
                'SignonScript' => '',
                'SignonSession' => 'session123',
                'host' => 'local',
                'port' => '12',
                'user' => 'user',
                'foo' => 'bar'
            ),
            $GLOBALS['cfg']['Server']
        );

        $this->assertEquals(
            'pmaToken',
            $_SESSION[' PMA_token ']
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

        // case 4
        $_REQUEST['old_usr'] = '';

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
    public function testAuthFails()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PMA\libraries\plugins\auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(5))
            ->method('auth');

        // case 1

        $GLOBALS['login_without_password_is_forbidden'] = true;

        $this->object->authFails();

        $this->assertEquals(
            'Login without a password is forbidden by configuration '
            . '(see AllowNoPassword)',
            $_SESSION['PMA_single_signon_error_message']
        );

        // case 2

        $GLOBALS['login_without_password_is_forbidden'] = null;
        $GLOBALS['allowDeny_forbidden'] = true;

        $this->object->authFails();

        $this->assertEquals(
            'Access denied!',
            $_SESSION['PMA_single_signon_error_message']
        );

        // case 3

        $GLOBALS['allowDeny_forbidden'] = null;
        $GLOBALS['no_activity'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = '1440';

        $this->object->authFails();

        $this->assertEquals(
            'No activity within 1440 seconds; please log in again.',
            $_SESSION['PMA_single_signon_error_message']
        );

        // case 4

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue('error<123>'));

        $dbi->expects($this->at(1))
            ->method('getError')
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['no_activity'] = null;

        $this->object->authFails();

        $this->assertEquals(
            'error&lt;123&gt;',
            $_SESSION['PMA_single_signon_error_message']
        );

        // case 5
        $this->object->authFails();

        $this->assertEquals(
            'Cannot log in to the MySQL server',
            $_SESSION['PMA_single_signon_error_message']
        );
    }
}
