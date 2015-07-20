<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for AuthenticationHttp class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/plugins/auth/AuthenticationHttp.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';
require_once 'libraries/Error_Handler.class.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * tests for AuthenticationHttp class
 *
 * @package PhpMyAdmin-test
 */
class PMA_AuthenticationHttp_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var AuthenticationHttp
     */
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config;
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['lang'] = "en";
        $GLOBALS['text_dir'] = "ltr";
        $GLOBALS['available_languages'] = array(
            "en" => array("English", "US-ENGLISH"),
            "ch" => array("Chinese", "TW-Chinese")
        );
        $GLOBALS['token_provided'] = true;
        $GLOBALS['token_mismatch'] = false;
        $this->object = new AuthenticationHttp();
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
     * Test for AuthenticationHttp::auth
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

        $_REQUEST['old_usr'] = '1';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'http://phpmyadmin.net/logout';

        $this->assertFalse(
            $this->object->auth()
        );

        $this->assertContains(
            'Location: http://phpmyadmin.net/logout',
            $GLOBALS['header'][0]
        );

        // case 2

        $restoreInstance = PMA_Response::getInstance();

        // mock footer
        $mockFooter = $this->getMockBuilder('PMA_Footer')
            ->disableOriginalConstructor()
            ->setMethods(array('setMinimal'))
            ->getMock();

        $mockFooter->expects($this->once())
            ->method('setMinimal')
            ->with();

        // mock header

        $mockHeader = $this->getMockBuilder('PMA_Header')
            ->disableOriginalConstructor()
            ->setMethods(
                array('setBodyId', 'setTitle', 'disableMenuAndConsole', 'addHTML')
            )
            ->getMock();

        $mockHeader->expects($this->once())
            ->method('setBodyId')
            ->with('loginform');

        $mockHeader->expects($this->once())
            ->method('setTitle')
            ->with('Access denied!');

        $mockHeader->expects($this->once())
            ->method('disableMenuAndConsole')
            ->with();

        // set mocked headers and footers
        $mockResponse = $this->getMockBuilder('PMA_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getHeader', 'getFooter', 'addHTML'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue($mockFooter));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue($mockHeader));

        $mockResponse->expects($this->exactly(6))
            ->method('addHTML')
            ->with();

        $attrInstance = new ReflectionProperty('PMA_Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        $GLOBALS['header'] = array();
        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['Server']['verbose'] = 'verboseMessagê';

        $this->assertFalse(
            $this->object->auth()
        );

        $this->assertEquals(
            array(
                'WWW-Authenticate: Basic realm="phpMyAdmin verboseMessag"',
                'HTTP/1.0 401 Unauthorized',
                'status: 401 Unauthorized'
            ),
            $GLOBALS['header']
        );

        $attrInstance->setValue($restoreInstance);

        // case 3

        $GLOBALS['header'] = array();
        $GLOBALS['cfg']['Server']['verbose'] = '';
        $GLOBALS['cfg']['Server']['host'] = 'hòst';
        $this->assertFalse(
            $this->object->auth()
        );

        $this->assertEquals(
            array(
                'WWW-Authenticate: Basic realm="phpMyAdmin hst"',
                'HTTP/1.0 401 Unauthorized',
                'status: 401 Unauthorized'
            ),
            $GLOBALS['header']
        );

        // case 4

        $GLOBALS['header'] = array();
        $GLOBALS['cfg']['Server']['host'] = '';
        $GLOBALS['cfg']['Server']['auth_http_realm'] = 'rêäealmmessage';
        $this->assertFalse(
            $this->object->auth()
        );

        $this->assertEquals(
            array(
                'WWW-Authenticate: Basic realm="realmmessage"',
                'HTTP/1.0 401 Unauthorized',
                'status: 401 Unauthorized'
            ),
            $GLOBALS['header']
        );
    }

    /**
     * Test for AuthenticationHttp::authCheck
     *
     * @param string $user           test username
     * @param string $pass           test password
     * @param string $userIndex      index to test username against
     * @param string $passIndex      index to test username against
     * @param string $expectedReturn expected return value from test
     * @param string $expectedUser   expected username to be set
     * @param string $expectedPass   expected password to be set
     * @param string $old_usr        value for $_REQUEST['old_usr']
     *
     * @return void
     * @dataProvider authCheckProvider
     */
    public function testAuthCheck($user, $pass, $userIndex, $passIndex,
        $expectedReturn, $expectedUser, $expectedPass, $old_usr = ''
    ) {
        $GLOBALS['PHP_AUTH_USER'] = '';
        $GLOBALS['PHP_AUTH_PW'] = '';

        $_SERVER[$userIndex] = $user;
        $_SERVER[$passIndex] = $pass;

        $_REQUEST['old_usr'] = $old_usr;

        $this->assertEquals(
            $expectedReturn,
            $this->object->authCheck()
        );

        $this->assertEquals(
            $expectedUser,
            $GLOBALS['PHP_AUTH_USER']
        );

        $this->assertEquals(
            $expectedPass,
            $GLOBALS['PHP_AUTH_PW']
        );

        $_SERVER[$userIndex] = null;
        $_SERVER[$passIndex] = null;
    }

    /**
     * Data provider for testAuthCheck
     *
     * @return array Test data
     */
    public function authCheckProvider()
    {
        return array(
            array(
                'Basic ' . base64_encode('foo:bar'),
                'pswd',
                'PHP_AUTH_USER',
                'PHP_AUTH_PW',
                false,
                '',
                'bar',
                'foo'
            ),
            array(
                'Basic ' . base64_encode('foobar'),
                'pswd',
                'REMOTE_USER',
                'REMOTE_PASSWORD',
                true,
                'Basic Zm9vYmFy',
                'pswd'
            ),
            array(
                'Basic ' . base64_encode('foobar:'),
                'pswd',
                'AUTH_USER',
                'AUTH_PASSWORD',
                true,
                'foobar',
                false
            ),
            array(
                'Basic ' . base64_encode(':foobar'),
                'pswd',
                'HTTP_AUTHORIZATION',
                'AUTH_PASSWORD',
                true,
                'Basic OmZvb2Jhcg==',
                'pswd'
            ),
            array(
                'BasicTest',
                'pswd',
                'Authorization',
                'AUTH_PASSWORD',
                true,
                'BasicTest',
                'pswd'
            ),
        );
    }

    /**
     * Test for AuthenticationHttp::authSetUser
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        // case 1

        $GLOBALS['PHP_AUTH_USER'] = 'testUser';
        $GLOBALS['PHP_AUTH_PW'] = 'testPass';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['Server']['user'] = 'testUser';

        $this->assertTrue(
            $this->object->authSetUser()
        );

        $this->assertEquals(
            'testUser',
            $GLOBALS['cfg']['Server']['user']
        );

        $this->assertEquals(
            'testPass',
            $GLOBALS['cfg']['Server']['password']
        );

        $this->assertFalse(
            isset($GLOBALS['PHP_AUTH_PW'])
        );

        $this->assertFalse(
            isset($_SERVER['PHP_AUTH_PW'])
        );

        $this->assertEquals(
            2,
            $GLOBALS['server']
        );

        // case 2
        $GLOBALS['PHP_AUTH_USER'] = 'testUser';
        $GLOBALS['PHP_AUTH_PW'] = 'testPass';
        $GLOBALS['cfg']['Servers'][1] = array(
            'host' => 'a',
            'user' => 'testUser',
            'foo' => 'bar'
        );

        $GLOBALS['cfg']['Server']= array(
            'host' => 'a',
            'user' => 'user2'
        );

        $this->assertTrue(
            $this->object->authSetUser()
        );

        $this->assertEquals(
            array(
                'user' => 'testUser',
                'password' => 'testPass',
                'host' => 'a',
                'foo' => 'bar'
            ),
            $GLOBALS['cfg']['Server']
        );

        $this->assertEquals(
            1,
            $GLOBALS['server']
        );

        // case 3
        $GLOBALS['server'] = 3;
        $GLOBALS['PHP_AUTH_USER'] = 'testUser';
        $GLOBALS['PHP_AUTH_PW'] = 'testPass';
        $GLOBALS['cfg']['Servers'][1] = array(
            'host' => 'a',
            'user' => 'testUsers',
            'foo' => 'bar'
        );

        $GLOBALS['cfg']['Server']= array(
            'host' => 'a',
            'user' => 'user2'
        );

        $this->assertTrue(
            $this->object->authSetUser()
        );

        $this->assertEquals(
            array(
                'user' => 'testUser',
                'password' => 'testPass',
                'host' => 'a'
            ),
            $GLOBALS['cfg']['Server']
        );

        $this->assertEquals(
            3,
            $GLOBALS['server']
        );
    }

    /**
     * Test for AuthenticationHttp::authSetFails
     *
     * @return void
     *
     * @group medium
     */
    public function testAuthFails()
    {

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
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
        $this->object->authFails();
        $result = ob_get_clean();

        $this->assertContains(
            '<p>error 123</p>',
            $result
        );

        $this->object = $this->getMockBuilder('AuthenticationHttp')
            ->disableOriginalConstructor()
            ->setMethods(array('authForm'))
            ->getMock();

        $this->object->expects($this->exactly(2))
            ->method('authForm');
        // case 2
        $GLOBALS['cfg']['Server']['host'] = 'host';
        $GLOBALS['errno'] = 1045;

        $this->assertTrue(
            $this->object->authFails()
        );

        // case 3
        $GLOBALS['errno'] = 1043;
        $this->assertTrue(
            $this->object->authFails()
        );
    }
}
