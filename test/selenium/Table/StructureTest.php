<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * @coversNothing
 */
class StructureTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' `val2` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');'
        );

        $this->login();
        $this->navigateTable('test_table');

        $this->waitForElement('xpath', "(//a[contains(., 'Structure')])")->click();

        $this->waitAjax();
        $this->waitForElement('id', 'tablestructure');
    }

    /**
     * Test for adding a new column
     *
     * @group large
     */
    public function testAddColumn(): void
    {
        $this->waitForElement('cssSelector', "#addColumns > input[value='Go']")->click();
        $this->waitAjax();

        $this->waitUntilElementIsPresent('className', 'append_fields_form', 30);

        $this->byId('field_0_1')->sendKeys('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->waitAjax();

        $this->byPartialLinkText('Structure')->click();
        $this->waitAjax();
        $this->waitForElement('id', 'tablestructure');

        self::assertEquals('val3', $this->byCssSelector('label[for=checkbox_row_4]')->getText());

        self::assertEquals('int(11)', $this->getCellByTableId('tablestructure', 4, 4));
    }

    /**
     * Test for changing a column
     *
     * @group large
     */
    public function testChangeColumn(): void
    {
        $this->byCssSelector('#tablestructure tbody tr:nth-child(2) td:nth-child(11)')->click();
        $this->waitAjax();

        $this->waitUntilElementIsPresent('className', 'append_fields_form', 30);

        self::assertEquals('val', $this->byId('field_0_1')->getAttribute('value'));
        $this->byId('field_0_1')->clear();
        $this->byId('field_0_1')->sendKeys('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->byPartialLinkText('Structure')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'tablestructure');

        self::assertEquals('val3', $this->waitForElement('cssSelector', 'label[for=checkbox_row_2]')->getText());
    }

    /**
     * Test for dropping columns
     *
     * @group large
     */
    public function testDropColumns(): void
    {
        $this->waitForElement('cssSelector', 'label[for=checkbox_row_2]')->click();
        $this->waitForElement('cssSelector', 'label[for=checkbox_row_3]')->click();
        $this->waitUntilElementIsPresent('xpath', '//button[contains(., "Drop")]', 30)->click();

        $this->waitForElement('cssSelector', "input[id='buttonYes']")->click();

        $this->waitForElement(
            'xpath',
            '//div[@class=\'alert alert-success\' and contains(., \'2 columns have been dropped successfully.\')]'
        );
        $this->waitAjax();

        self::assertFalse($this->isElementPresent(
            'cssSelector',
            'label[for=checkbox_row_2]'
        ));
    }
}
