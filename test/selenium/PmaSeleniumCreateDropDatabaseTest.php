<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

class PmaSeleniumCreateDropDatabaseTest extends PHPUnit_Extensions_SeleniumTestCase
{

    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    public function testCreateDropDatabase()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->selectFrame("frame_content");
        $this->click("link=Databases");
        $this->waitForPageToLoad("30000");
        $this->type("id=text_create_db", "pma");
        $this->click("id=buttonGo");
        $this->assertTrue($this->isTextPresent("pma"));

        $this->click("link=pma");
        $this->waitForPageToLoad("30000");
        $this->click("link=Operations");
        $this->waitForPageToLoad("30000");
        $this->click("id=drop_db_anchor");
        $this->click("//button[@type='button']");

    }
}
