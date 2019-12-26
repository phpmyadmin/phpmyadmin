<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * StructureTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class StructureTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    protected function setUp(): void
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

        $this->login();
        $this->navigateTable('test_table');

        $this->waitForElement(
            'xpath',
            "(//a[contains(., 'Structure')])"
        )->click();

        $this->waitAjax();
        $this->waitForElement('id', "tablestructure");
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
            'cssSelector',
            "#addColumns > input[value='Go']"
        )->click();
        $this->waitAjax();

        $this->waitForElement('className', "append_fields_form");

        $this->byId("field_0_1")->sendKeys('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->waitAjax();
        $this->waitForElement(
            'xpath',
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->byPartialLinkText("Structure")->click();
        $this->waitAjax();
        $this->waitForElement('id', "tablestructure");

        $this->assertEquals(
            "val3",
            $this->byCssSelector('label[for=checkbox_row_4]')->getText()
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

        $this->waitForElement('className', "append_fields_form");

        $this->assertEquals("val", $this->byId("field_0_1")->getAttribute('value'));
        $this->byId("field_0_1")->clear();
        $this->byId("field_0_1")->sendKeys('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and contains(., "
            . "'Table test_table has been altered successfully')]"
        );

        $this->byPartialLinkText("Structure")->click();
        $this->waitAjax();

        $this->waitForElement('id', "tablestructure");

        $this->assertEquals(
            "val3",
            $this->waitForElement('cssSelector', 'label[for=checkbox_row_2]')->getText()
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
        $this->waitForElement('cssSelector', 'label[for=checkbox_row_2]')->click();
        $this->waitForElement('cssSelector', 'label[for=checkbox_row_3]')->click();
        $this->byXPath(
            "//button[@name='submit_mult' and contains(., 'Drop')]"
        )->click();

        $this->waitForElement(
            'cssSelector',
            "input[id='buttonYes']"
        )->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and contains(., "
            . "'Your SQL query has been executed successfully')]"
        );
        $this->waitAjax();

        $this->assertFalse(
            $this->isElementPresent(
                'cssSelector',
                'label[for=checkbox_row_2]'
            )
        );
    }
}
