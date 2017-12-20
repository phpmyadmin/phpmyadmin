<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationHttp class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Plugins\Auth\AuthenticationHttp;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationHttp class
 *
 * @package PhpMyAdmin-test
 */
class AuthenticationHttpTest extends PmaTestCase
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
    protected function setUp()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Servers'] = array();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['lang'] = "en";
        $GLOBALS['text_dir'] = "ltr";
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
        parent::tearDown();
        unset($this->object);
    }

    public function doMockResponse($set_minimal, $body_id, $set_title)
    {
        // mock footer
        $mockFooter = $this->getMockBuilder('PhpMyAdmin\Footer')
            ->disableOriginalConstructor()
            ->setMethods(array('setMinimal'))
            ->getMock();

        $mockFooter->expects($this->exactly($set_minimal))
            ->method('setMinimal')
            ->with();

        // mock header

        $mockHeader = $this->getMockBuilder('PhpMyAdmin\Header')
            ->disableOriginalConstructor()
            ->setMethods(
                array('setBodyId', 'setTitle', 'disableMenuAndConsole', 'addHTML')
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
        $headers = array_slice(func_get_args(), 3);
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

        if (!empty($_REQUEST['old_usr'])) {
            $this->object->logOut();
        } else {
            $this->assertFalse(
                $this->object->showLoginForm()
            );
        }
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationHttp::showLoginForm
     *
     * @return void
     */
    public function testAuthLogoutUrl()
    {

        $_REQUEST['old_usr'] = '1';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'https://example.com/logout';

        $this->doMockResponse(
            0, 0, 0,
            array('Location: https://example.com/logout')
        );
    }

    public function testAuthVerbose()
    {
        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['Server']['verbose'] = 'verboseMessagê';

        $this->doMockResponse(
            1, 1, 1,
            array('WWW-Authenticate: Basic realm="phpMyAdmin verboseMessag"'),
            array('status: 401 Unauthorized'),
            401
        );
    }

    public function testAuthHost()
    {
        $GLOBALS['cfg']['Server']['verbose'] = '';
        $GLOBALS['cfg']['Server']['host'] = 'hòst';

        $this->doMockResponse(
            1, 1, 1,
            array('WWW-Authenticate: Basic realm="phpMyAdmin hst"'),
            array('status: 401 Unauthorized'),
            401
        );
    }

    public function testAuthRealm()
    {
        $GLOBALS['cfg']['Server']['host'] = '';
        $GLOBALS['cfg']['Server']['auth_http_realm'] = 'rêäealmmessage';

        $this->doMockResponse(
            1, 1, 1,
            array('WWW-Authenticate: Basic realm="realmmessage"'),
            array('status: 401 Unauthorized'),
            401
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationHttp::readCredentials
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
     * @dataProvider readCredentialsProvider
     */
    public function testAuthCheck($user, $pass, $userIndex, $passIndex,
        $expectedReturn, $expectedUser, $expectedPass, $old_usr = ''
    ) {
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
    public function readCredentialsProvider()
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
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationHttp::storeCredentials
     *
     * @return void
     */
    public function testAuthSetUser()
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
            $this->object->storeCredentials()
        );

        $this->assertEquals(
            array(
                'user' => 'testUser',
                'password' => 'testPass',
                'host' => 'a',
            ),
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
            $this->object->storeCredentials()
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
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationHttp::authSetFails
     *
     * @return void
     *
     * @group medium
     */
    public function testAuthFails()
    {

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $this->assertContains(
            '<p>error 123</p>',
            $result
        );

        $this->object = $this->getMockBuilder('PhpMyAdmin\Plugins\Auth\AuthenticationHttp')
            ->disableOriginalConstructor()
            ->setMethods(array('authForm'))
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
