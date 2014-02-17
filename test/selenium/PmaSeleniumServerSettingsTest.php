<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for settings related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumServerSettingsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PMA_SeleniumSettingsTest extends PMA_SeleniumBase
{
    /**
     * Name of database for the test
     *
     * @var string
     */
    private $_dbname;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->dbConnect();
        $this->_dbname = TESTSUITE_DATABASE;
        $this->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->dbQuery('USE ' . $this->_dbname);
    }

    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $more = $this->byLinkText("More");
        $this->moveto($more);
        $this->waitForElement("byLinkText", "Settings")->click();
        $this->waitForElement(
            "byXPath", "//a[@class='tabactive' and contains(., 'Settings')]"
        );
    }

    /**
     * Tests whether hiding a database works or not
     *
     * @return void
     *
     * @group large
     */
    public function testHideDatabase()
    {
        $this->byLinkText("Features")->click();

        $this->waitForElement("byId", "Servers-1-hide_db")
            ->value($this->_dbname);
        $this->byName("submit_save")->click();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertFalse(
            $this->isElementPresent("byLinkText", $this->_dbname)
        );

        $this->byCssSelector("a[href='#Servers-1-hide_db']")->click();
        $this->byName("submit_save")->click();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertTrue(
            $this->isElementPresent("byLinkText", $this->_dbname)
        );
    }

    /**
     * Tests whether the various settings tabs are displayed when clicked
     *
     * @return void
     *
     * @group large
     */
    public function testSettingsTabsAreDisplayed()
    {
        $this->byLinkText("SQL queries")->click();
        $this->waitForElement('byClassName', 'tabs');

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
     *
     * @group large
     */
    public function testHideLogo()
    {
        $this->byLinkText("Navigation panel")->click();

        $this->waitForElement("byName", "NavigationDisplayLogo")
            ->click();
        $this->byName("submit_save")->click();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertFalse(
            $this->isElementPresent("byId", "imgpmalogo")
        );

        $this->byCssSelector("a[href='#NavigationDisplayLogo']")->click();
        $this->byName("submit_save")->click();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
        );
        $this->assertTrue(
            $this->isElementPresent("byId", "imgpmalogo")
        );
    }

    /**
     * Tear Down function for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        $this->dbQuery('DROP DATABASE IF EXISTS ' . $this->_dbname);
    }

}
