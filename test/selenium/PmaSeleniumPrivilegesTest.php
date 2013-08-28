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
class PmaSeleniumPrivilegesTest extends PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * Helper Object
     *
     * @var Helper
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
        $this->byLinkText("Change password")->click();

        $e = $this->_helper->waitForElement("byId", "change_password_anchor");
        try {
            $ele = $this->_helper->waitForElement("byId", "text_pma_pw");
            $this->assertEquals("", $ele->value());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->_helper->waitForElement("byId", "text_pma_pw2");
            $this->assertEquals("", $ele->value());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->_helper->waitForElement("byId", "generated_pw");
            $this->assertEquals("", $ele->value());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->byId("button_generate_password")->click();
        $this->assertNotEquals("", $this->byId("text_pma_pw")->value());
        $this->assertNotEquals("", $this->byId("text_pma_pw2")->value());
        $this->assertNotEquals("", $this->byId("generated_pw")->value());

        if (TESTSUITE_PASSWORD != "") {
            $this->byId("text_pma_pw")->value(TESTSUITE_PASSWORD);
            $this->byId("text_pma_pw2")->value(TESTSUITE_PASSWORD);
        } else {
            $this->byId("nopass_1")->click();
        }

        $this->byCssSelector("span.ui-button-text:nth-child(1)")->click();
        $ele = $this->_helper->waitForElement("byCssSelector", "div.success");
        $this->assertEquals(
            "The profile has been updated.",
            $ele->text()
        );
    }
}

?>
