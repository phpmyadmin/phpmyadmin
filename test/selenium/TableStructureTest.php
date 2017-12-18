<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * TableStructureTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class TableStructureTest extends TestBase
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
        parent::setUpPage();

        $this->login();
        $this->navigateTable('test_table');

        $this->waitForElement(
            "byXPath",
            "(//a[contains(., 'Structure')])"
        )->click();

        $this->waitAjax();
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
        $this->waitForElement(
            'byCssSelector',
            "#addColumns > input[value='Go']"
        )->click();
        $this->waitAjax();

        $this->waitForElement("byClassName", "append_fields_form");

        $this->byId("field_0_1")->value('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->waitAjax();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->byPartialLinkText("Structure")->click();
        $this->waitAjax();
        $this->waitForElement("byId", "tablestructure");

        $this->assertEquals(
            "val3",
            $this->byCssSelector('label[for=checkbox_row_4]')->text()
        );

        $this->assertEquals(
            "int(11)",
            $this->getCellByTableId('tablestructure', 4, 4)
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
        $this->byCssSelector(
            "#tablestructure tbody tr:nth-child(2) td:nth-child(11)"
        )->click();
        $this->waitAjax();

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

        $this->byPartialLinkText("Structure")->click();
        $this->waitAjax();

        $this->waitForElement("byId", "tablestructure");

        $this->assertEquals(
            "val3",
            $this->waitForElement('byCssSelector', 'label[for=checkbox_row_2]')->text()
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
        $this->waitForElement('byCssSelector', 'label[for=checkbox_row_2]')->click();
        $this->waitForElement('byCssSelector', 'label[for=checkbox_row_3]')->click();
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
        $this->waitAjax();

        $this->assertFalse(
            $this->isElementPresent(
                'byCssSelector', 'label[for=checkbox_row_2]'
            )
        );
    }
}
