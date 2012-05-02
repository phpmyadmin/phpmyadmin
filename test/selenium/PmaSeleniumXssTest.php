<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for XSS related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

class PmaSeleniumXSSTest extends PHPUnit_Extensions_SeleniumTestCase
{
    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    public function testQueryTabWithNullValue()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->selectFrame("frame_content");
        $this->click("link=SQL");
        $this->waitForPageToLoad("30000");
        $this->click("button_submit_query");
        $this->assertAlert("Missing value in the form!");
    }
}
?>
