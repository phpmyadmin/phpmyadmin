<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumTableCreateTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTableCreateTest extends PHPUnit_Extensions_Selenium2TestCase
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
    }

    /**
     * Creates a table
     *
     * @return void
     */
    public function testCreateTable()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText($this->_dbname)->click();

        $this->_helper->waitForElement('byId', 'create_table_form_minimal');
        $this->byCssSelector(
            "form#create_table_form_minimal input[name=table]"
        )->value("test_table");
        $this->byName("num_fields")->value("4");
        $this->byCssSelector('input[value=Go]')->click();
        $this->_helper->waitForElement('byName', 'do_save_data');

        // column details
        $column_text_details = array(
            "field_0_1" => "test_id",
            "field_0_3" => "14",
            "field_0_10" => "comm1",
            "field_1_1" => "test_column",
            "field_1_3" => "10",
            "field_1_10" => "comm2",
        );

        foreach ($column_text_details as $field => $val) {
            $this->byId($field)->value($val);
        }

        $column_dropdown_details = array(
            "field_0_6" => "UNSIGNED",
            "field_0_8" => "PRIMARY",
            "field_1_2" => "VARCHAR",
            "field_1_5" => "utf8_general_ci",
            "field_1_4" => "As defined:"
        );

        foreach ($column_dropdown_details as $selector => $value) {
            $sel = $this->select($this->byId($selector));
            $sel->selectOptionByLabel($value);
        }

        $this->byName("field_default_value[1]")->value("def");
        $this->byId("field_0_9")->click(); // auto increment
        $this->byId("field_1_7")->click(); // null

        // post
        $this->byName("do_save_data")->click();
        $this->_helper->waitForElement("byLinkText", "test_table");

        $this->_tableStructureAssertions();
    }

    /**
     * Make assertions for table structure
     *
     * @return void
     */
    private function _tableStructureAssertions()
    {
        // go to structure page
        $this->byLinkText("Structure")->click();
        $this->_helper->waitForElement("byId", "tablestructure");

        // make assertions for first row
        $this->assertContains(
            "test_id",
            $this->byCssSelector('label[for=checkbox_row_1]')->text()
        );

        $this->assertEquals(
            "int(14)",
            $this->_helper->getTable("tablestructure.1.4")
        );

        $this->assertEquals(
            "UNSIGNED",
            $this->_helper->getTable("tablestructure.1.6")
        );

        $this->assertEquals(
            "No",
            $this->_helper->getTable("tablestructure.1.7")
        );

        $this->assertEquals(
            "None",
            $this->_helper->getTable("tablestructure.1.8")
        );

        $this->assertEquals(
            "AUTO_INCREMENT",
            $this->_helper->getTable("tablestructure.1.9")
        );

        $this->assertFalse(
            $this->_helper->isElementPresent(
                'byCssSelector',
                'table#tablestructure tbody tr:nth-child(1) "
                . "ul.table-structure-actions li.primary a'
            )
        );

        // make assertions for second row
        $this->assertContains(
            "test_column",
            $this->byCssSelector('label[for=checkbox_row_2]')->text()
        );

        $this->assertEquals(
            "varchar(10)",
            $this->_helper->getTable("tablestructure.2.4")
        );

        $this->assertEquals(
            "utf8_general_ci",
            $this->_helper->getTable("tablestructure.2.5")
        );

        $this->assertEquals(
            "Yes",
            $this->_helper->getTable("tablestructure.2.7")
        );

        $this->assertEquals(
            "def",
            $this->_helper->getTable("tablestructure.2.8")
        );

        $this->assertFalse(
            $this->_helper->isElementPresent(
                'byCssSelector',
                'css=ul.table-structure-actions:nth-child(2) li.primary a'
            )
        );
    }

    /**
     * Tear down functions for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        $this->_helper->dbQuery('DROP DATABASE ' . $this->_dbname);
    }
}
