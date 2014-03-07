<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for export related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumExportTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumExportTest extends PHPUnit_Extensions_Selenium2TestCase
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
            "INSERT INTO `test_table` (val) VALUES (2);"
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
    }

    /**
     * Test for server level export
     *
     * @param string $plugin   Export format
     * @param array  $expected Array of expected strings
     *
     * @return void
     * @dataProvider exportDataProvider
     */
    public function testServerImport($plugin, $expected)
    {
        $text = $this->_doExport('server', $plugin);

        foreach ($expected as $str) {
            $this->assertContains($str, $text);
        }

    }

    /**
     * Test for db level export
     *
     * @param string $plugin   Export format
     * @param array  $expected Array of expected strings
     *
     * @return void
     * @dataProvider exportDataProvider
     */
    public function testDbExport($plugin, $expected)
    {
        $this->_helper->waitForElement("byLinkText", $this->_dbname)->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: ". $this->_dbname ."')]"
        );

        $text = $this->_doExport('db', $plugin);

        foreach ($expected as $str) {
            $this->assertContains($str, $text);
        }
    }

    /**
     * Test for table level export
     *
     * @param string $plugin   Export format
     * @param array  $expected Array of expected strings
     *
     * @return void
     * @dataProvider exportDataProvider
     */
    public function testTableExport($plugin, $expected)
    {
        $this->_helper->dbQuery("INSERT INTO `test_table` (val) VALUES (3);");

        // go to database page
        $this->_helper->waitForElement("byLinkText", $this->_dbname)->click();

        // got to table page
        $this->_helper->waitForElement("byLinkText", "test_table")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='tabactive' and contains(., 'Browse')]"
        );

        $text = $this->_doExport('table', $plugin);

        foreach ($expected as $str) {
            $this->assertContains($str, $text);
        }
    }


    /**
     * Data provider for testServerExport
     *
     * @return array Test cases data
     */
    public function exportDataProvider()
    {
        return array(
            array(
                'CSV',
                array('"1","2"')
            ),
            array(
                'SQL',
                array(
                    "CREATE TABLE IF NOT EXISTS `test_table`",
                    "INSERT INTO `test_table` (`id`, `val`) VALUES (1, 2);"
                )
            ),
            array(
                'JSON',
                array('[{"id":"1","val":"2"}]')
            )
        );
    }

    /**
     * Function that goes to the import page, uploads a file and submit form
     *
     * @param string $type   level: server, db or import
     * @param string $plugin format: csv, json, etc
     *
     * @return string export string
     */
    private function _doExport($type, $plugin)
    {
        $this->byLinkText("Export")->click();

        $this->_helper->waitForElement("byName", "dump");
        $this->byCssSelector("label[for=radio_custom_export]")->click();

        if ($type == 'server') {
            $this->byLinkText('Unselect All')->click();
            $this->byCssSelector("option[value=" . $this->_dbname . "]")->click();
        }

        if ($type == 'table') {
            $this->byCssSelector("label[for=radio_allrows_0]")->click();
            $this->byName("limit_to")->clear();
            $this->byName("limit_to")->value("1");
        }

        $this->select($this->byId("plugins"))->selectOptionByLabel($plugin);
        $this->byCssSelector("label[for=radio_view_as_text]")->click();

        if ($plugin == "SQL") {
            $this->byCssSelector(
                "label[for=radio_sql_structure_or_data_structure_and_data]"
            )->click();

            if ($type != "table") {
                $this->byCssSelector(
                    "label[for=checkbox_sql_create_database]"
                )->click();
            }
        }

        $this->byId("buttonGo")->click();

        $text = $this->_helper->waitForElement("byId", "textSQLDUMP")->text();

        return $text;
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
