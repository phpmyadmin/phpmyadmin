<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for creating and deleting databases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
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
     * Name of database for the test
     * 
     * @var string
     */
    private $_dbname;

    /**
     * Helper Object
     * 
     * @var obj
     */
    private $_helper;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $this->_helper = new Helper($this);
        $this->setBrowser($this->_helper->getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
        
    }

    /**
     * Creates a database and drops it
     *
     * @return void
     */
    public function testCreateDropDatabase()
    {
        $this->_dbname = 'pma_testdb' . time();
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click("link=Databases");
        $this->waitForElementPresent("id=text_create_db");
        $this->type("id=text_create_db", $this->_dbname);
        $this->click("id=buttonGo");
        $this->waitForElementPresent("css=span.ajax_notification div.success");
        $this->assertElementPresent("css=span.ajax_notification div.success");
        $this->_dropDatabase();
    }

    /**
     * Drops a database, called after testCreateDropDatabase
     * 
     * @return void
     */
    private function _dropDatabase()
    {
        $this->_helper->gotoHomepage();
        $this->click("css=ul#topmenu a:contains('Databases')");
        $this->waitForElementNotPresent('css=div#loading_parent');
        $this->click(
            "css=input[name='selected_dbs[]'][value='" . $this->_dbname . "']"
        );
        $this->click("css=button.mult_submit");
        $this->click("css=button:contains('OK')");
        $this->waitForElementNotPresent(
            "css=input[name='selected_dbs[]'][value='" . $this->_dbname . "']"
        );
        $this->assertElementPresent("css=span.ajax_notification div.success");
    }
}
