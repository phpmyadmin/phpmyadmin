<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

/**
 * PmaSeleniumLoginTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumLoginTest extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    /**
     * Test for successful login
     *
     * @return void
     */
    public function testSuccessfulLogin()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->assertTrue($log->isSuccessLogin());
        Helper::logOutIfLoggedIn($this);
    }

    /**
     * Test for unsuccessful login
     *
     * @return void
     */
    public function testLoginWithWrongPassword()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login("Admin", "Admin");
        $this->assertTrue($log->isUnsuccessLogin());
        Helper::logOutIfLoggedIn($this);
    }
}
?>
