<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for change password related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * PrivilegesTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class ChangePasswordTest extends TestBase
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

        $e = $this->waitForElement("byId", "change_password_anchor");
        $e->click();

        $this->waitAjax();

        $this->waitForElement('byXpath', "//span[contains(., 'Change password')]");
        try {
            $ele = $this->waitForElement("byName", "pma_pw");
            $this->assertEquals("", $ele->value());
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->waitForElement("byName", "pma_pw2");
            $this->assertEquals("", $ele->value());
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->waitForElement("byName", "generated_pw");
            $this->assertEquals("", $ele->value());
        } catch (\PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->byId("button_generate_password")->click();
        $this->assertNotEquals("", $this->byName("pma_pw")->value());
        $this->assertNotEquals("", $this->byName("pma_pw2")->value());
        $this->assertNotEquals("", $this->byName("generated_pw")->value());

        if ($GLOBALS['TESTSUITE_PASSWORD'] != "") {
            $this->byName("pma_pw")->clear();
            $this->byName("pma_pw2")->clear();
            $this->byName("pma_pw")->value($GLOBALS['TESTSUITE_PASSWORD']);
            $this->byName("pma_pw2")->value($GLOBALS['TESTSUITE_PASSWORD']);
        } else {
            $this->byId("nopass_1")->click();
        }

        $this->byXpath("//button[contains(., 'Go')]")->click();
        $ele = $this->waitForElement("byCssSelector", "div.success");
        $this->assertEquals(
            "The profile has been updated.",
            $ele->text()
        );
    }
}
