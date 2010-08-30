<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for XSS related tests
 *
 * @package phpMyAdmin-test
 */

require_once('PmaSeleniumTestCase.php');

class PmaSeleniumXSSTest extends PmaSeleniumTestCase 
{
    public function testXssQueryTab() 
    {
        $this->doLogin();
        $this->selectFrame("frame_content");
        $this->click("link=SQL");
        $this->waitForPageToLoad("30000");
        $this->type("sqlquery", "'\"><script>alert(123);</script>");
        $this->click("SQL");
        // If an alert pops up the test fails, since we don't handle an alert.
    }
}
?>
