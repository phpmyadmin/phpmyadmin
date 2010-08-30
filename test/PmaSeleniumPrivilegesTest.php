<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for privilege related tests
 *
 * @package phpMyAdmin-test
 */

require_once('PmaSeleniumTestCase.php');


class PmaSeleniumPrivilegesTest extends PmaSeleniumTestCase 
{
    public function testChangePassword() 
    {
        $this->doLogin();
        $this->selectFrame("frame_content");
        $this->click("link=Change password");
        $this->waitForPageToLoad("30000");
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
        $this->type("text_pma_pw", $this->cfg['Test']['testuser']['password']);
        $this->type("text_pma_pw2", $this->cfg['Test']['testuser']['password']);
        $this->click("change_pw");
        $this->waitForPageToLoad("30000");
        $this->assertTrue($this->isTextPresent(""));
        $this->assertTrue($this->isTextPresent(""));
    }
}
?>
