<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for tracking related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumTrackingTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumTrackingTest extends PMA_SeleniumBase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->dbQuery(
            "CREATE TABLE `test_table_2` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->dbQuery(
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
        $this->login();

        $this->skipIfNotPMADB();

        $this->waitForElement('byLinkText', $this->database_name)->click();
        $this->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: "
            . $this->database_name . "')]"
        );
        $this->expandMore();
        $this->byLinkText("Tracking")->click();
        $this->waitForElement("byLinkText", "Track table");
        $this->byXPath("(//a[contains(., 'Track table')])[1]")->click();

        $this->waitForElement("byName", "delete")->click();
        $this->byCssSelector("input[value='Create version']")->click();
        $this->waitForElement("byId", "versions");
    }

    /**
     * Tests basic tracking functionality
     *
     * @return void
     *
     * @group large
     */
    public function testTrackingData()
    {
        $this->_executeSqlAndReturnToTableTracking();

        $this->byLinkText("Tracking report")->click();
        $this->waitForElement(
            "byXPath",
            "//h3[contains(., 'Tracking report')]"
        );

        $this->assertContains(
            "DROP TABLE IF EXISTS `test_table`",
            $this->getCellByTableId('ddl_versions', 1, 4)
        );

        $this->assertContains(
            "CREATE TABLE `test_table` (",
            $this->getCellByTableId('ddl_versions', 2, 4)
        );

        $this->assertContains(
            "UPDATE test_table SET val = val + 1",
            $this->getCellByTableId('dml_versions', 1, 4)
        );

        $this->assertNotContains(
            "DELETE FROM test_table WHERE val = 3",
            $this->byId("dml_versions")->text()
        );

        // only structure
        $this->select($this->byName("logtype"))
            ->selectOptionByLabel("Structure only");
        $this->byCssSelector("input[value='Go']")->click();

        $this->waitForElementNotPresent("byId", "loading_parent");

        $this->assertFalse(
            $this->isElementPresent("byId", "dml_versions")
        );

        $this->assertContains(
            "DROP TABLE IF EXISTS `test_table`",
            $this->getCellByTableId('ddl_versions', 1, 4)
        );

        $this->assertContains(
            "CREATE TABLE `test_table` (",
            $this->getCellByTableId('ddl_versions', 2, 4)
        );

        // only data
        $this->select($this->byName("logtype"))
            ->selectOptionByLabel("Data only");
        $this->byCssSelector("input[value='Go']")->click();

        $this->waitForElementNotPresent("byId", "loading_parent");

        $this->assertFalse(
            $this->isElementPresent("byId", "ddl_versions")
        );

        $this->assertContains(
            "UPDATE test_table SET val = val + 1",
            $this->getCellByTableId('dml_versions', 1, 4)
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
     *
     * @group large
     */
    public function testDeactivateTracking()
    {
        $this->byCssSelector("input[value='Deactivate now']")->click();
        $this->waitForElement(
            "byCssSelector", "input[value='Activate now']"
        );
        $this->_executeSqlAndReturnToTableTracking();
        $this->assertFalse(
            $this->isElementPresent("byId", "dml_versions")
        );
    }

    /**
     * Tests dropping a tracking
     *
     * @return void
     *
     * @group large
     */
    public function testDropTracking()
    {
        $this->byLinkText("Database: " . $this->database_name)->click();
        $this->waitForElement("byCssSelector", "table.data");
        usleep(1000000);
        $this->expandMore();
        $this->byLinkText("Tracking")->click();
        $this->waitForElement("byId", "versions");
        $this->byLinkText("Drop")->click();

        $this->waitForElement(
            "byXPath",
            "//button[contains(., 'OK')]"
        )->click();

        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Your SQL query has been executed')]"
        );

        $this->assertContains(
            "test_table",
            $this->getCellByTableId('noversions', 1, 1)
        );

        $this->assertContains(
            "test_table_2",
            $this->getCellByTableId('noversions', 2, 1)
        );
    }

    /**
     * Tests structure snapshot of a tracking
     *
     * @return void
     *
     * @group large
     */
    public function testStructureSnapshot()
    {
        $this->byLinkText("Structure snapshot")->click();
        $this->waitForElement("byId", "tablestructure");

        $this->assertContains(
            "id",
            $this->getCellByTableId('tablestructure', 1, 1)
        );

        $this->assertContains(
            "val",
            $this->getCellByTableId('tablestructure', 2, 1)
        );

        $this->assertContains(
            "PRIMARY",
            $this->getCellByTableId('tablestructure_indexes', 1, 1)
        );

        $this->assertContains(
            "id",
            $this->getCellByTableId('tablestructure_indexes', 1, 5)
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
        $this->waitForElement("byId", "queryfieldscontainer");
        $this->typeInTextArea(
            ";UPDATE test_table SET val = val + 1; "
            . "DELETE FROM test_table WHERE val = 3"
        );
        $this->byCssSelector("input[value='Go']")->click();
        $this->waitForElement("byClassName", "success");

        $this->expandMore();
        $this->byLinkText("Tracking")->click();
        $this->waitForElement("byId", "versions");
    }
}
