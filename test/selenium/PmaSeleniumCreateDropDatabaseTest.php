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

/**
 * PmaSeleniumCreateDropDatabaseTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumCreateDropDatabaseTest extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    /**
     * Creates a database and drops it
     *
     * @return void
     */
    public function testCreateDropDatabase()
    {
        $log = new PmaSeleniumTestCase($this);
        $dbname = 'pma_testdb' . time();
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click("link=Databases");
        $this->waitForElementPresent("id=text_create_db");
        $this->type("id=text_create_db", $dbname);
        $this->click("id=buttonGo");
        $this->waitForElementPresent("css=span.ajax_notification");
        $this->assertElementPresent("css=span.ajax_notification div.success");
        $this->click("css=input[name='selected_dbs[]'][value='" . $dbname . "']");
        $this->click("css=button.mult_submit");
        $this->click("css=button:contains('OK')");
        $this->waitForElementPresent("css=span.ajax_notification");
        $this->assertElementPresent("css=span.ajax_notification div.success");
    }
}
