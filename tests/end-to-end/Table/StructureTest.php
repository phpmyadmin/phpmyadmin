<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
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
            . ');',
        );

        $this->login();
        $this->navigateTable('test_table');

        $this->waitForElement('xpath', "(//a[contains(., 'Structure')])")->click();

        $this->waitAjax();
        $this->waitForElement('id', 'tablestructure');
    }

    /**
     * Test for adding a new column
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

        self::assertSame(
            'val3',
            $this->byCssSelector('label[for=checkbox_row_4]')->getText(),
        );

        self::assertSame(
            'int(11)',
            $this->getCellByTableId('tablestructure', 4, 4),
        );
    }

    /**
     * Test for changing a column
     */
    public function testChangeColumn(): void
    {
        $this->byCssSelector('#tablestructure tbody tr:nth-child(2) td:nth-child(11)')->click();
        $this->waitAjax();

        $this->waitUntilElementIsPresent('className', 'append_fields_form', 30);

        self::assertSame('val', $this->byId('field_0_1')->getAttribute('value'));
        $this->byId('field_0_1')->clear();
        $this->byId('field_0_1')->sendKeys('val3');
        $this->byCssSelector("input[name='do_save_data']")->click();

        $this->byPartialLinkText('Structure')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'tablestructure');

        self::assertSame(
            'val3',
            $this->waitForElement('cssSelector', 'label[for=checkbox_row_2]')->getText(),
        );
    }

    /**
     * Test for dropping columns
     */
    public function testDropColumns(): void
    {
        $this->waitForElement('cssSelector', 'label[for=checkbox_row_2]')->click();
        $this->waitForElement('cssSelector', 'label[for=checkbox_row_3]')->click();
        $this->waitUntilElementIsPresent('xpath', '//button[contains(., "Drop")]', 30)->click();

        $this->waitForElement('cssSelector', "input[id='buttonYes']")->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('2 columns have been dropped successfully.', $success->getText());
        $this->waitAjax();

        self::assertFalse(
            $this->isElementPresent(
                'cssSelector',
                'label[for=checkbox_row_2]',
            ),
        );
    }
}
