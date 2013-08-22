<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for privilege related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'Helper.php';

/**
 * PmaSeleniumPrivilegesTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumPrivilegesTest extends PHPUnit_Extensions_SeleniumTestCase
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
     * Tests the changing of the password
     *
     * @return void
     */
    public function testChangePassword()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click("link=Change password");
        $this->waitForElementPresent("id=change_password_anchor");
        try {
            $this->waitForElementPresent("id=text_pma_pw");
            $this->assertEquals("", $this->getValue("text_pma_pw"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $this->waitForElementPresent("id=text_pma_pw2");
            $this->assertEquals("", $this->getValue("text_pma_pw2"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $this->waitForElementPresent("id=generated_pw");
            $this->assertEquals("", $this->getValue("generated_pw"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->click("button_generate_password");
        $this->assertNotEquals("", $this->getValue("text_pma_pw"));
        $this->assertNotEquals("", $this->getValue("text_pma_pw2"));
        $this->assertNotEquals("", $this->getValue("generated_pw"));

        if (TESTSUITE_PASSWORD != "") {
            $this->type("text_pma_pw", TESTSUITE_PASSWORD);
            $this->type("text_pma_pw2", TESTSUITE_PASSWORD);
            $this->click("css=button:contains('Go')");
        } else {
            $this->click("id=nopass_1");
            $this->click("css=button:contains('Go')");
        }		 		 

        $this->waitForElementPresent("id=result_query");
        $this->assertTrue($this->isTextPresent("The profile has been updated."));
    } 
}

?>
