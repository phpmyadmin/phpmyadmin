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
 * PmaSeleniumTableStructureTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTableStructureTest extends PHPUnit_Extensions_Selenium2TestCase
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
        $this->_dbname = 'pma_db_test';
        $this->_helper->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->_helper->dbQuery('USE ' . $this->_dbname);
        $this->_helper->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " `val2` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
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
            "(//a[contains(., 'Structure')])[2]"
        )->click();

        $this->_helper->waitForElement("byId", "tablestructure");
    }

    /**
     * Test for adding a new column
     *
     * @return void
     */
    public function testAddColumn()
    {
        $this->byCssSelector("label[for='field_where_after']")->click();
        $this->byCssSelector("input[value='Go']")->click();

        $this->_helper->waitForElement("byClassName", "append_fields_form");

        $this->byId("field_0_1")->value('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->byLinkText("Structure")->click();
        $this->_helper->waitForElement("byId", "tablestructure");

        $this->assertEquals(
            "val3",
            $this->byCssSelector('label[for=checkbox_row_2]')->text()
        );

        $this->assertEquals(
            "int(11)",
            $this->_helper->getTable("tablestructure.2.4")
        );
    }

    /**
     * Test for changing a column
     *
     * @return void
     */
    public function testChangeColumn()
    {
        $this->byXPath("(//a[contains(., 'Change')])[2]")->click();

        $this->_helper->waitForElement("byClassName", "append_fields_form");

        $this->assertEquals("val", $this->byId("field_0_1")->value());
        $this->byId("field_0_1")->clear();
        $this->byId("field_0_1")->value('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->assertEquals(
            "val3",
            $this->byCssSelector('label[for=checkbox_row_2]')->text()
        );
    }

    /**
     * Test for dropping columns
     *
     * @return void
     */
    public function testDropColumns()
    {
        $this->byCssSelector('label[for=checkbox_row_2]')->click();
        $this->byCssSelector('label[for=checkbox_row_3]')->click();
        $this->byXPath(
            "//button[@class='mult_submit' and contains(., 'Drop')]"
        )->click();

        $this->_helper->waitForElement(
            "byCssSelector", "input[id='buttonYes']"
        )->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Your SQL query has been executed successfully')]"
        );

        $this->assertFalse(
            $this->_helper->isElementPresent(
                'byCssSelector', 'label[for=checkbox_row_2]'
            )
        );
    }

    /**
     * Tear Down function for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        $this->_helper->dbQuery('DROP DATABASE ' . $this->_dbname);
    }
}
