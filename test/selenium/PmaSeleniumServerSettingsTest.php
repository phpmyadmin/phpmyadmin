<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for settings related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumServerSettingsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumSettingsTest extends PHPUnit_Extensions_Selenium2TestCase
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
        $this->_helper->dbConnect();
        $this->_dbname = 'pma_db_' . time();
        $this->_helper->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->_helper->dbQuery('USE ' . $this->_dbname);
    }

    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $more = $this->byLinkText("More");
        $this->moveto($more);
        $this->_helper->waitForElement("byLinkText", "Settings")->click();
        $this->_helper->waitForElement(
            "byXPath", "//a[@class='tabactive' and contains(., 'Settings')]"
        );
    }

    /**
     * Tests whether hiding a database works or not
     *
     * @return void
     */
    public function testHideDatabase()
    {
        $this->byLinkText("Features")->click();

        $this->_helper->waitForElement("byId", "Servers-1-hide_db")
            ->value($this->_dbname);
        $this->byName("submit_save")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertFalse(
            $this->_helper->isElementPresent("byLinkText", $this->_dbname)
        );

        $this->byCssSelector("a[href='#Servers-1-hide_db']")->click();
        $this->byName("submit_save")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertTrue(
            $this->_helper->isElementPresent("byLinkText", $this->_dbname)
        );
    }

    /**
     * Tests whether the various settings tabs are displayed when clicked
     *
     * @return void
     */
    public function testSettingsTabsAreDisplayed()
    {
        $this->byLinkText("SQL queries")->click();
        $this->_helper->waitForElement('byClassName', 'tabs');

        $this->byLinkText("SQL Query box")->click();
        $this->assertTrue(
            $this->byId("Sql_box")->displayed()
        );
        $this->assertFalse(
            $this->byId("Sql_queries")->displayed()
        );

        $this->byCssSelector("a[href='#Sql_queries']")->click();
        $this->assertFalse(
            $this->byId("Sql_box")->displayed()
        );
        $this->assertTrue(
            $this->byId("Sql_queries")->displayed()
        );
    }

    /**
     * Tests if hiding the logo works or not
     *
     * @return void
     */
    public function testHideLogo()
    {
        $this->byLinkText("Navigation panel")->click();

        $this->_helper->waitForElement("byName", "NavigationDisplayLogo")
            ->click();
        $this->byName("submit_save")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertFalse(
            $this->_helper->isElementPresent("byId", "imgpmalogo")
        );

        $this->byCssSelector("a[href='#NavigationDisplayLogo']")->click();
        $this->byName("submit_save")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertTrue(
            $this->_helper->isElementPresent("byId", "imgpmalogo")
        );
    }

    /**
     * Tear Down function for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        $this->_helper->dbQuery('DROP DATABASE IF EXISTS ' . $this->_dbname);
    }

}
