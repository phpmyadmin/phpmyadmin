<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationSignon class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Plugins\Auth\AuthenticationSignon;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationSignon class
 *
 * @package PhpMyAdmin-test
 */
class AuthenticationSignonTest extends PmaTestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $this->object = new AuthenticationSignon();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showLoginForm
     *
     * @return void
     */
    public function testAuth()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = '';

        ob_start();
        $this->object->showLoginForm();
        $result = ob_get_clean();

        $this->assertContains(
            'You must set SignonURL!',
            $result
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showLoginForm
     *
     * @return void
     */
    public function testAuthLogoutURL()
    {
        $this->mockResponse('Location: https://example.com/logoutURL');

        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logoutURL';

        $this->object->logOut();
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showLoginForm
     *
     * @return void
     */
    public function testAuthLogout()
    {
        $this->mockResponse('Location: https://example.com/SignonURL');

        $GLOBALS['header'] = array();
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['LogoutURL'] = '';

        $this->object->logOut();
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::readCredentials
     *
     * @return void
     */
    public function testAuthCheckEmpty()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'https://example.com/SignonDiffURL';

        $this->assertFalse(
            $this->object->readCredentials()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::readCredentials
     *
     * @return void
     */
    public function testAuthCheckSession()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $_SESSION['LAST_SIGNON_URL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['SignonScript'] = './examples/signon-script.php';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['SignonCookieParams'] = array();
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

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::readCredentials
     *
     * @return void
     */
    public function testAuthCheckToken()
    {
        $this->mockResponse('Location: https://example.com/SignonURL');

        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['SignonCookieParams'] = array();
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
                'SignonURL' => 'https://example.com/SignonURL',
                'SignonScript' => '',
                'SignonSession' => 'session123',
                'SignonCookieParams' => array(),
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

        $this->assertArrayNotHasKey(
            'LAST_SIGNON_URL',
            $_SESSION
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::readCredentials
     *
     * @return void
     */
    public function testAuthCheckKeep()
    {
        $GLOBALS['cfg']['Server']['SignonURL'] = 'https://example.com/SignonURL';
        $GLOBALS['cfg']['Server']['SignonSession'] = 'session123';
        $GLOBALS['cfg']['Server']['SignonCookieParams'] = array();
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

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::storeCredentials
     *
     * @return void
     */
    public function testAuthSetUser()
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

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showFailure
     *
     * @return void
     */
    public function testAuthFailsForbidden()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('showLoginForm'))
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

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showFailure
     *
     * @return void
     */
    public function testAuthFailsDeny()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('showLoginForm'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $this->object->showFailure('allow-denied');

        $this->assertEquals(
            'Access denied!',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showFailure
     *
     * @return void
     */
    public function testAuthFailsTimeout()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('showLoginForm'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $GLOBALS['cfg']['LoginCookieValidity'] = '1440';

        $this->object->showFailure('no-activity');

        $this->assertEquals(
            'No activity within 1440 seconds; please log in again.',
            $_SESSION['PMA_single_signon_error_message']
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showFailure
     *
     * @return void
     */
    public function testAuthFailsMySQLError()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('showLoginForm'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationSignon::showFailure
     *
     * @return void
     */
    public function testAuthFailsConnect()
    {
        $GLOBALS['cfg']['Server']['SignonSession'] = 'newSession';
        $_COOKIE['newSession'] = '42';

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Auth\AuthenticationSignon')
            ->disableOriginalConstructor()
            ->setMethods(array('showLoginForm'))
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('showLoginForm');

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
}
