<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for privilege related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumPrivilegesTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumPrivilegesTest extends PMA_SeleniumBase
{
    /**
     * Tests the changing of the password
     *
     * @return void
     *
     * @group large
     */
    public function testChangePassword()
    {
        $this->login();
        $this->waitForElement('byLinkText', "Change password")->click();

        $e = $this->waitForElement("byId", "change_password_anchor");
        try {
            $ele = $this->waitForElement("byId", "text_pma_pw");
            $this->assertEquals("", $ele->value());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->waitForElement("byId", "text_pma_pw2");
            $this->assertEquals("", $ele->value());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->waitForElement("byId", "generated_pw");
            $this->assertEquals("", $ele->value());
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->byId("button_generate_password")->click();
        $this->assertNotEquals("", $this->byId("text_pma_pw")->value());
        $this->assertNotEquals("", $this->byId("text_pma_pw2")->value());
        $this->assertNotEquals("", $this->byId("generated_pw")->value());

        if ($GLOBALS['TESTSUITE_PASSWORD'] != "") {
            $this->byId("text_pma_pw")->clear();
            $this->byId("text_pma_pw2")->clear();
            $this->byId("text_pma_pw")->value($GLOBALS['TESTSUITE_PASSWORD']);
            $this->byId("text_pma_pw2")->value($GLOBALS['TESTSUITE_PASSWORD']);
        } else {
            $this->byId("nopass_1")->click();
        }

        $this->byCssSelector("span.ui-button-text:nth-child(1)")->click();
        $ele = $this->waitForElement("byCssSelector", "div.success");
        $this->assertEquals(
            "The profile has been updated.",
            $ele->text()
        );
    }
}
