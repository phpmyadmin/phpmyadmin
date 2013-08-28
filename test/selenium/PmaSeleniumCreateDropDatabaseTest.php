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
class PmaSeleniumCreateDropDatabaseTest extends PHPUnit_Extensions_Selenium2TestCase
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
     * @var Helper
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

        $this->byLinkText("Databases")->click();

        $element = $this->_helper->waitForElement('byId', 'text_create_db');
        $element->value($this->_dbname);

        $this->byId("buttonGo")->click();

        $element = $this->_helper->waitForElement(
            "byCssSelector", "span.ajax_notification div.success"
        );

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

        $this->byLinkText("Databases")->click();
        $this->_helper->waitForElementNotPresent('byCssSelector', 'div#loading_parent');

        $this->byCssSelector(
            "input[name='selected_dbs[]'][value='" . $this->_dbname . "']"
        )->click();

        $this->byCssSelector("button.mult_submit")->click();
        $this->byCssSelector("span.ui-button-text:nth-child(1)")->click();

        $this->_helper->waitForElementNotPresent(
            "byCssSelector", "input[name='selected_dbs[]'][value='" . $this->_dbname . "']"
        );

        $this->_helper->waitForElement(
            "byCssSelector", "span.ajax_notification div.success"
        );
    }
}
