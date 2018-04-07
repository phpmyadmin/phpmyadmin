<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationConfig class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for PhpMyAdmin\Plugins\Auth\AuthenticationConfig class
 *
 * @package PhpMyAdmin-test
 */
class AuthenticationConfigTest extends PmaTestCase
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
        $GLOBALS['token_provided'] = true;
        $GLOBALS['token_mismatch'] = false;
        $this->object = new AuthenticationConfig();
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
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::showLoginForm
     *
     * @return void
     */
    public function testAuth()
    {
        $this->assertTrue(
            $this->object->showLoginForm()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::readCredentials
     *
     * @return void
     */
    public function testAuthCheck()
    {
        $GLOBALS['cfg']['Server'] = array(
            'user' => 'username',
            'password' => 'password',
        );
        $this->assertTrue(
            $this->object->readCredentials()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::storeCredentials
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        $this->assertTrue(
            $this->object->storeCredentials()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Auth\AuthenticationConfig::showFailure
     *
     * @return void
     */
    public function testAuthFails()
    {
        $removeConstant = false;
        $GLOBALS['error_handler'] = new ErrorHandler;
        $GLOBALS['cfg']['Servers'] = array(1);
        $GLOBALS['allowDeny_forbidden'] = false;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        ob_start();
        $this->object->showFailure('');
        $html = ob_get_clean();

        $this->assertContains(
            'You probably did not create a configuration file. You might want ' .
            'to use the <a href="setup/">setup script</a> to create one.',
            $html
        );

        $this->assertContains(
            '<strong>MySQL said: </strong><a href="./url.php?url=https%3A%2F%2F' .
            'dev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Ferror-messages-server.html"' .
            ' target="mysql_doc">' .
            '<img src="themes/dot.gif" title="Documentation" alt="Documentation" ' .
            'class="icon ic_b_help" /></a>',
            $html
        );

        $this->assertContains(
            'Cannot connect: invalid settings.',
            $html
        );

        $this->assertContains(
            '<a href="index.php?server=0&amp;lang=en" '
            . 'class="button disableAjax">Retry to connect</a>',
            $html
        );
    }
}
