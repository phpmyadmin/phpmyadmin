<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for change password related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\AssertionFailedError;

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
     * Array of AssertionFailedError->toString
     *
     * @var string[]
     */
    private $verificationErrors;

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

        $e = $this->waitForElement('id', "change_password_anchor");
        $e->click();

        $this->waitAjax();

        $this->waitForElement('xpath', "//span[contains(., 'Change password')]");
        try {
            $ele = $this->waitForElement('name', "pma_pw");
            $this->assertEquals("", $ele->getAttribute('value'));
        } catch (AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->waitForElement('name', "pma_pw2");
            $this->assertEquals("", $ele->getAttribute('value'));
        } catch (AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $ele = $this->waitForElement('name', "generated_pw");
            $this->assertEquals("", $ele->getAttribute('value'));
        } catch (AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        $this->byId("button_generate_password")->click();
        $this->assertNotEquals("", $this->byName("pma_pw")->getAttribute('value'));
        $this->assertNotEquals("", $this->byName("pma_pw2")->getAttribute('value'));
        $this->assertNotEquals("", $this->byName("generated_pw")->getAttribute('value'));

        if ($GLOBALS['TESTSUITE_PASSWORD'] != "") {
            $this->byName("pma_pw")->clear();
            $this->byName("pma_pw2")->clear();

            $this->byName('pma_pw')->click()->sendKeys($GLOBALS['TESTSUITE_PASSWORD']);

            $this->byName('pma_pw2')->click()->sendKeys($GLOBALS['TESTSUITE_PASSWORD']);
        } else {
            $this->byId("nopass_1")->click();
        }

        $this->byXpath("//button[contains(., 'Go')]")->click();
        $ele = $this->waitForElement('cssSelector', "div.success");
        $this->assertEquals(
            "The profile has been updated.",
            trim($ele->getText())
        );
    }
}
