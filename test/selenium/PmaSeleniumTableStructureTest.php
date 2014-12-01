<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumTableStructureTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumTableStructureTest extends PMA_SeleniumBase
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
        $this->login();
        $this->waitForElement('byLinkText', $this->database_name)->click();

        $this->navigateTable('test_table');

        $this->waitForElement(
            "byXPath",
            "(//a[contains(., 'Structure')])"
        )->click();

        $this->waitForElement("byId", "tablestructure");
    }

    /**
     * Test for adding a new column
     *
     * @return void
     *
     * @group large
     */
    public function testAddColumn()
    {
        $this->byCssSelector("label[for='field_where_after']")->click();
        $this->byCssSelector("input[value='Go']")->click();

        $this->waitForElement("byClassName", "append_fields_form");

        $this->byId("field_0_1")->value('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->byLinkText("Structure")->click();
        $this->waitForElement("byId", "tablestructure");

        $this->assertEquals(
            "val3",
            $this->byCssSelector('label[for=checkbox_row_2]')->text()
        );

        $this->assertEquals(
            "int(11)",
            $this->getCellByTableId('tablestructure', 2, 4)
        );
    }

    /**
     * Test for changing a column
     *
     * @return void
     *
     * @group large
     */
    public function testChangeColumn()
    {
        $this->byXPath("(//a[contains(., 'Change')])[2]")->click();

        $this->waitForElement("byClassName", "append_fields_form");

        $this->assertEquals("val", $this->byId("field_0_1")->value());
        $this->byId("field_0_1")->clear();
        $this->byId("field_0_1")->value('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->byLinkText("Structure")->click();
        $this->waitForElement("byId", "tablestructure");

        $this->assertEquals(
            "val3",
            $this->byCssSelector('label[for=checkbox_row_2]')->text()
        );
    }

    /**
     * Test for dropping columns
     *
     * @return void
     *
     * @group large
     */
    public function testDropColumns()
    {
        $this->byCssSelector('label[for=checkbox_row_2]')->click();
        $this->byCssSelector('label[for=checkbox_row_3]')->click();
        $this->byXPath(
            "//button[@class='mult_submit' and contains(., 'Drop')]"
        )->click();

        $this->waitForElement(
            "byCssSelector", "input[id='buttonYes']"
        )->click();

        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Your SQL query has been executed successfully')]"
        );

        $this->assertFalse(
            $this->isElementPresent(
                'byCssSelector', 'label[for=checkbox_row_2]'
            )
        );
    }
}
