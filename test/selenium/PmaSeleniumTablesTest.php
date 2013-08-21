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
 * PmaSeleniumTablesTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTablesTest extends PHPUnit_Extensions_SeleniumTestCase
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
    }

    /**
     * Creates a table
     *
     * @return void
     */
    public function testCreateTable()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click('link='. $this->_dbname.'');
        $this->waitForElementPresent('id=create_table_form_minimal');
        $this->type(
            "css=form#create_table_form_minimal input[name=\"table\"]",
            "test_table"
        );
        $this->type("name=num_fields", "4");
        $this->click('css=input[value="Go"]');
        $this->waitForElementPresent('name=do_save_data');

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
            $this->type("id=" . $field, $val);
        }

        $column_dropdown_details = array(
            "field_0_6" => "UNSIGNED",
            "field_0_8" => "PRIMARY",
            "field_1_2" => "VARCHAR",
            "field_1_5" => "utf8_general_ci",
            "field_1_4" => "As defined:"
        );

        foreach ($column_dropdown_details as $selector => $value) {
            $this->select("id=" . $selector, $value);
        }

        $this->type("name=field_default_value[1]", "def");
        $this->click("id=field_0_9"); // auto increment
        $this->click("id=field_1_7"); // null

        // post
        $this->click("name=do_save_data");
        $this->waitForElementPresent("link=test_table");

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
        $this->click("css=a:contains(\"Structure\"):eq(1)");
        $this->waitForElementPresent("id=tablestructure");

        // make assertions for first row
        $this->assertElementContainsText(
            'css=label[for=checkbox_row_1]',
            "test_id"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.1.3"),
            "int(14)"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.1.5"),
            "UNSIGNED"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.1.6"),
            "No"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.1.7"),
            "None"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.1.8"),
            "AUTO_INCREMENT"
        );

        $this->assertElementNotPresent(
            'css=ul.table-structure-actions:eq(0) li.primary a'
        );

        // make assertions for second row
        $this->assertElementContainsText(
            'css=label[for=checkbox_row_2]',
            "test_column"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.2.3"),
            "varchar(10)"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.2.4"),
            "utf8_general_ci"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.2.6"),
            "Yes"
        );

        $this->assertEquals(
            $this->getTable("tablestructure.2.7"),
            "def"
        );

        $this->assertElementPresent(
            'css=ul.table-structure-actions:eq(1) li.primary a'
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
