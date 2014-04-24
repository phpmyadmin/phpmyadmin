<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for import related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumImportTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumImportTest extends PMA_SeleniumBase
{
    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->login();
    }

    /**
     * Test for server level import
     *
     * @return void
     *
     * @group large
     */
    public function testServerImport()
    {
        $this->_doImport('server');
        $result = $this->dbQuery("SHOW DATABASES LIKE 'test_import%'");
        $this->assertGreaterThanOrEqual(2, $result->num_rows);

        // clear db
        $this->dbQuery("DROP DATABASE test_import1");
        $this->dbQuery("DROP DATABASE test_import2");
    }

    /**
     * Test for db level import
     *
     * @return void
     *
     * @group large
     */
    public function testDbImport()
    {
        $this->dbQuery("CREATE DATABASE " . $this->database_name);
        $this->waitForElement("byLinkText", $this->database_name)->click();
        $this->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: "
            . $this->database_name . "')]"
        );

        $this->_doImport("db");

        $this->dbQuery("USE " . $this->database_name);
        $result = $this->dbQuery("SHOW TABLES");
        $this->assertEquals(1, $result->num_rows);
    }

    /**
     * Test for table level import
     *
     * @return void
     *
     * @group large
     */
    public function testTableImport()
    {
        // setup the db
        $this->dbQuery("CREATE DATABASE " . $this->database_name);
        $this->dbQuery("USE " . $this->database_name);
        $this->dbQuery(
            "CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL)"
        );

        $this->navigateTable('test_table');

        $this->_doImport("table");

        $result = $this->dbQuery("SELECT * FROM test_table");
        $this->assertEquals(2, $result->num_rows);
    }

    /**
     * Function that goes to the import page, uploads a file and submit form
     *
     * @param string $type level: server, db or import
     *
     * @return void
     */
    private function _doImport($type)
    {
        /* FIXME: Need to implement file upload compatible with remote Selenium */
        $this->markTestIncomplete(
            'File uploading not yet implemented in Selenium test'
        );
        $this->waitForElement('byLinkText', "Import")->click();
        $ele = $this->waitForElement("byId", "input_import_file");
        $ele->value(
            dirname(__FILE__) . "/../test_data/" . $type . "_import.sql"
        );
        $this->byId("buttonGo")->click();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Import has been successfully')]"
        );
    }
}
