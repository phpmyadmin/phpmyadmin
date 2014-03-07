<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for tracking related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumTrackingTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTrackingTest extends PHPUnit_Extensions_Selenium2TestCase
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
        $this->_helper->dbConnect();
        $this->_dbname = 'pma_db_' . time();
        $this->_helper->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->_helper->dbQuery('USE ' . $this->_dbname);
        $this->_helper->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->_helper->dbQuery(
            "CREATE TABLE `test_table_2` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->_helper->dbQuery(
            "INSERT INTO `test_table` (val) VALUES (2), (3);"
        );
    }

    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText($this->_dbname)->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: ". $this->_dbname ."')]"
        );
        $ele = $this->byLinkText("More");
        $this->moveto($ele);
        $this->byLinkText("Tracking")->click();
        $this->_helper->waitForElement("byLinkText", "Track table");
        $this->byXPath("(//a[contains(., 'Track table')])[1]")->click();

        $this->_helper->waitForElement("byName", "delete")->click();
        $this->byCssSelector("input[value='Create version']")->click();
        $this->_helper->waitForElement("byId", "versions");
    }

    /**
     * Tests basic tracking functionality
     *
     * @return void
     */
    public function testTrackingData()
    {
        $this->_executeSqlAndReturnToTableTracking();

        $this->byLinkText("Tracking report")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//h3[contains(., 'Tracking report')]"
        );

        $this->assertContains(
            "DROP TABLE IF EXISTS `test_table`",
            $this->_helper->getTable("ddl_versions.1.4")
        );

        $this->assertContains(
            "CREATE TABLE `test_table` (",
            $this->_helper->getTable("ddl_versions.2.4")
        );

        $this->assertContains(
            "UPDATE test_table SET val = val + 1",
            $this->_helper->getTable("dml_versions.1.4")
        );

        $this->assertNotContains(
            "DELETE FROM test_table WHERE val = 3",
            $this->byId("dml_versions")->text()
        );

        // only structure
        $this->select($this->byName("logtype"))
            ->selectOptionByLabel("Structure only");
        $this->byCssSelector("input[value='Go']")->click();

        $this->_helper->waitForElementNotPresent("byId", "loading_parent");

        $this->assertFalse(
            $this->_helper->isElementPresent("byId", "dml_versions")
        );

        $this->assertContains(
            "DROP TABLE IF EXISTS `test_table`",
            $this->_helper->getTable("ddl_versions.1.4")
        );

        $this->assertContains(
            "CREATE TABLE `test_table` (",
            $this->_helper->getTable("ddl_versions.2.4")
        );

        // only data
        $this->select($this->byName("logtype"))
            ->selectOptionByLabel("Data only");
        $this->byCssSelector("input[value='Go']")->click();

        $this->_helper->waitForElementNotPresent("byId", "loading_parent");

        $this->assertFalse(
            $this->_helper->isElementPresent("byId", "ddl_versions")
        );

        $this->assertContains(
            "UPDATE test_table SET val = val + 1",
            $this->_helper->getTable("dml_versions.1.4")
        );

        $this->assertNotContains(
            "DELETE FROM test_table WHERE val = 3",
            $this->byId("dml_versions")->text()
        );
    }

    /**
     * Tests deactivation of tracking
     *
     * @return void
     */
    public function testDeactivateTracking()
    {
        $this->byCssSelector("input[value='Deactivate now']")->click();
        $this->_helper->waitForElement(
            "byCssSelector", "input[value='Activate now']"
        );
        $this->_executeSqlAndReturnToTableTracking();
        $this->assertFalse(
            $this->_helper->isElementPresent("byId", "dml_versions")
        );
    }

    /**
     * Tests dropping a tracking
     *
     * @return void
     */
    public function testDropTracking()
    {
        $this->byLinkText("Database: " . $this->_dbname)->click();
        $this->_helper->waitForElement("byCssSelector", "table.data");
        usleep(1000000);
        $ele = $this->byLinkText("More");
        $this->moveto($ele);
        $this->byLinkText("Tracking")->click();
        $this->_helper->waitForElement("byId", "versions");
        $this->byLinkText("Drop")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//button[contains(., 'OK')]"
        )->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Your SQL query has been executed')]"
        );

        $this->assertContains(
            "test_table",
            $this->_helper->getTable("noversions.1.1")
        );

        $this->assertContains(
            "test_table_2",
            $this->_helper->getTable("noversions.2.1")
        );
    }

    /**
     * Tests structure snapshot of a tracking
     *
     * @return void
     */
    public function testStructureSnapshot()
    {
        $this->byLinkText("Structure snapshot")->click();
        $this->_helper->waitForElement("byId", "tablestructure");

        $this->assertContains(
            "id",
            $this->_helper->getTable("tablestructure.1.1")
        );

        $this->assertContains(
            "val",
            $this->_helper->getTable("tablestructure.2.1")
        );

        $this->assertContains(
            "PRIMARY",
            $this->_helper->getTable("tablestructure_indexes.1.1")
        );

        $this->assertContains(
            "id",
            $this->_helper->getTable("tablestructure_indexes.1.5")
        );
    }

    /**
     * Goes to SQL tab, executes queries, returns to tracking page
     *
     * @return void
     */
    private function _executeSqlAndReturnToTableTracking()
    {
        $this->byLinkText("SQL")->click();
        $this->_helper->waitForElement("byId", "queryfieldscontainer");
        $this->_helper->typeInTextArea(
            ";UPDATE test_table SET val = val + 1; "
            . "DELETE FROM test_table WHERE val = 3"
        );
        $this->byCssSelector("input[value='Go']")->click();
        $this->_helper->waitForElement("byClassName", "success");

        $ele = $this->byLinkText("More");
        $this->moveto($ele);
        $this->byLinkText("Tracking")->click();
        $this->_helper->waitForElement("byId", "versions");
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
