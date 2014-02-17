<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'TestBase.php';

/**
 * PmaSeleniumLoginTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumLoginTest extends PMA_SeleniumBase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setBrowserUrl(SELENIUM_URL);
    }

    /**
     * Test for successful login
     *
     * @return void
     *
     * @group large
     */
    public function testSuccessfulLogin()
    {
        $this->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->assertTrue($this->isSuccessLogin());
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     *
     * @return void
     *
     * @group large
     */
    public function testLoginWithWrongPassword()
    {
        $this->login("Admin", "Admin");
        $this->assertTrue($this->isUnsuccessLogin());
        $this->logOutIfLoggedIn();
    }
}
?>
