<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for import related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumImportTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumImportTest extends PHPUnit_Extensions_Selenium2TestCase
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
    }

    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
    }

    /**
     * Test for server level import
     *
     * @return void
     */
    public function testServerImport()
    {
        $this->_doImport('server');
        $result = $this->_helper->dbQuery("SHOW DATABASES LIKE 'test_import%'");
        $this->assertGreaterThanOrEqual(2, $result->num_rows);

        // clear db
        $this->_helper->dbQuery("DROP DATABASE test_import1");
        $this->_helper->dbQuery("DROP DATABASE test_import2");
    }

    /**
     * Test for db level import
     *
     * @return void
     */
    public function testDbImport()
    {
        $this->_helper->dbQuery("CREATE DATABASE " . $this->_dbname);
        $this->byLinkText("Databases")->click();
        $this->_helper->waitForElement("byLinkText", $this->_dbname)->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: ". $this->_dbname ."')]"
        );

        $this->_doImport("db");

        $this->_helper->dbQuery("USE " . $this->_dbname);
        $result = $this->_helper->dbQuery("SHOW TABLES");
        $this->assertEquals(1, $result->num_rows);
    }

    /**
     * Test for table level import
     *
     * @return void
     */
    public function testTableImport()
    {
        // setup the db
        $this->_helper->dbQuery("CREATE DATABASE " . $this->_dbname);
        $this->_helper->dbQuery("USE " . $this->_dbname);
        $this->_helper->dbQuery(
            "CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL)"
        );

        // go to database page
        $this->byLinkText("Databases")->click();
        $this->_helper->waitForElement("byLinkText", $this->_dbname)->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: ". $this->_dbname ."')]"
        );

        // got to table page
        $this->_helper->waitForElement("byLinkText", "test_table")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='tabactive' and contains(., 'Browse')]"
        );

        $this->_doImport("table");

        $result = $this->_helper->dbQuery("SELECT * FROM test_table");
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
        $this->byLinkText("Import")->click();
        $ele = $this->_helper->waitForElement("byId", "input_import_file");
        $ele->value(
            dirname(__FILE__) . "/../test_data/" . $type . "_import.sql"
        );
        $this->byId("buttonGo")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Import has been successfully')]"
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
