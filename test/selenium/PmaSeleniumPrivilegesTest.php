<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for privilege related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

class PmaSeleniumPrivilegesTest extends PHPUnit_Extensions_SeleniumTestCase
{
    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    public function testChangePassword()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->selectFrame("frame_content");
        $this->click("link=Change password");
        $this->waitForPageToLoad("20000");
        try {
            $this->assertEquals("", $this->getValue("text_pma_pw"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $this->assertEquals("", $this->getValue("text_pma_pw2"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
        try {
            $this->assertEquals("", $this->getValue("generated_pw"));
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            array_push($this->verificationErrors, $e->toString());
        }
            $this->click("button_generate_password");
            $this->assertNotEquals("", $this->getValue("text_pma_pw"));
            $this->assertNotEquals("", $this->getValue("text_pma_pw2"));
            $this->assertNotEquals("", $this->getValue("generated_pw"));
            $this->type("text_pma_pw", TESTSUITE_PASSWORD);
            $this->type("text_pma_pw2", TESTSUITE_PASSWORD);
            $this->click("//button[@type='button']");
            $this->waitForPageToLoad("20000");
            $this->assertTrue($this->isTextPresent(""));
            $this->assertTrue($this->isTextPresent(""));
    }
}
?>
