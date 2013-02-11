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
        $dbname = "pma_testdb".time();
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click("link=Databases");
        $this->waitForElementPresent("id=text_create_db");
        $this->type("id=text_create_db", $dbname);
        $this->click("id=buttonGo");
        $this->waitForElementPresent("css=span.ajax_notification");
        $this->assertElementPresent("css=span.ajax_notification div.success");	    	
        $this->click("xpath=(//input[@name='selected_dbs[]'])[@value='".$dbname."']");
        $this->click("css=button.mult_submit.ajax");
        $this->click("css=button:contains('OK')");
        $this->waitForElementPresent("css=span.ajax_notification");
        $this->assertElementPresent("css=span.ajax_notification div.success");
    }
}
