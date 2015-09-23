<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for export related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumExportTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumExportTest extends PMA_SeleniumBase
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
        $this->login();
    }

    /**
     * Test for server level export
     *
     * @param string $plugin   Export format
     * @param array  $expected Array of expected strings
     *
     * @return void
     * @dataProvider exportDataProvider
     *
     * @group large
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
     *
     * @group large
     */
    public function testDbExport($plugin, $expected)
    {
        $this->waitForElement("byLinkText", $this->database_name)->click();
        $this->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: "
            . $this->database_name . "')]"
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
     *
     * @group large
     */
    public function testTableExport($plugin, $expected)
    {
        $this->dbQuery("INSERT INTO `test_table` (val) VALUES (3);");

        $this->navigateTable('test_table');

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
                    "INSERT INTO `test_table` (`id`, `val`) VALUES",
                    "(1, 2);"
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
        $this->expandMore();

        $this->waitForElement('byLinkText', "Export")->click();
        $this->sleep();

        $this->waitForElement("byId", "quick_or_custom");
        /*
         * FIXME: There should be better way to wait for javascript to be executed
         */
        $this->sleep();
        $this->byCssSelector("label[for=radio_custom_export]")->click();

        if ($type == 'server') {
            $this->byLinkText('Unselect all')->click();
            $this->byCssSelector(
                "option[value=" . $this->database_name . "]"
            )->click();
        }

        if ($type == 'table') {
            $this->byCssSelector("label[for=radio_allrows_0]")->click();
            $this->sleep();
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

        $text = $this->waitForElement("byId", "textSQLDUMP")->text();

        return $text;
    }
}
