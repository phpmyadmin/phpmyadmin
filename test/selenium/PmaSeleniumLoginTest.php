<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
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
     * Helper Object
     * 
     * @var obj
     */
    private $_helper;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $this->_helper = new Helper($this);
        $this->setBrowser($this->_helper->getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    /**
     * Test for successful login
     *
     * @return void
     */
    public function testSuccessfulLogin()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->assertTrue($this->_helper->isSuccessLogin());
        $this->_helper->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     *
     * @return void
     */
    public function testLoginWithWrongPassword()
    {
        $this->_helper->login("Admin", "Admin");
        $this->assertTrue($this->_helper->isUnsuccessLogin());
        $this->_helper->logOutIfLoggedIn();
    }
}
?>
