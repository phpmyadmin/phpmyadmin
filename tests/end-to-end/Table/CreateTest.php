<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

use function sleep;

#[CoversNothing]
#[Large]
class CreateTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->login();
        $this->navigateDatabase($this->databaseName);
    }

    /**
     * Creates a table
     */
    public function testCreateTable(): void
    {
        $this->waitAjax();
        $this->waitAjax();

        $this->waitForElement('id', 'createTableMinimalForm');
        $this->byId('createTableNameInput')->sendKeys('test_table');
        $numFieldsInput = $this->byId('createTableNumFieldsInput');
        $numFieldsInput->clear();
        $numFieldsInput->sendKeys('4');
        $this->byCssSelector('#createTableMinimalForm input[value=Create]')->click();

        $this->waitAjax();
        $this->waitForElement('name', 'do_save_data');

        $this->waitForElement('id', 'field_1_7')->click(); // null
        $this->waitForElement('id', 'field_0_9')->click(); // auto increment

        // column details
        $columnTextDetails = [
            'field_0_1' => 'test_id',
            'field_0_3' => '14',
            'field_0_10' => 'comm1',
            'field_1_1' => 'test_column',
            'field_1_3' => '10',
            'field_1_10' => 'comm2',
        ];

        foreach ($columnTextDetails as $field => $val) {
            $this->byId($field)->sendKeys($val);
        }

        $columnDropdownDetails = [
            'field_0_6' => 'UNSIGNED',
            'field_1_2' => 'VARCHAR',
            'field_1_4' => 'As defined:',
        ];

        foreach ($columnDropdownDetails as $selector => $value) {
            $this->waitForElement(
                'xpath',
                '//select[@id=\'' . $selector . '\']//option[contains(text(), \'' . $value . '\')]',
            )->click();
        }

        // click to load select options
        $this->byId('field_1_5')->click();
        $this->waitForElement(
            'xpath',
            '//select[@id=\'field_1_5\']//option[contains(text(), \'utf8mb4_general_ci\')]',
        )->click();

        $this->byName('field_default_value[1]')->sendKeys('def');

        $this->scrollToBottom();
        $ele = $this->waitForElement('name', 'do_save_data');
        $this->moveto($ele);
        // post
        $ele->click();
        $this->waitForElement('cssSelector', 'li.last.nav_node_table');

        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'test_table');
        sleep(1);
        $this->tableStructureAssertions();
    }

    /**
     * Make assertions for table structure
     */
    private function tableStructureAssertions(): void
    {
        $this->navigateTable('test_table', true);

        $this->waitAjax();

        // go to structure page
        $this->waitForElement('partialLinkText', 'Structure')->click();

        $this->waitForElement('id', 'tablestructure');
        $this->waitForElement('id', 'table_structure_id');

        // make assertions for first row
        self::assertStringContainsString(
            'test_id',
            $this->byCssSelector('label[for=checkbox_row_1]')->getText(),
        );

        self::assertSame(
            'int(14)',
            $this->getCellByTableId('tablestructure', 1, 4),
        );

        self::assertSame(
            'UNSIGNED',
            $this->getCellByTableId('tablestructure', 1, 6),
        );

        self::assertSame(
            'No',
            $this->getCellByTableId('tablestructure', 1, 7),
        );

        self::assertSame(
            'None',
            $this->getCellByTableId('tablestructure', 1, 8),
        );
        self::assertSame(
            'comm1',
            $this->getCellByTableId('tablestructure', 1, 9),
        );

        self::assertSame(
            'AUTO_INCREMENT',
            $this->getCellByTableId('tablestructure', 1, 10),
        );

        self::assertFalse($this->isElementPresent(
            'cssSelector',
            'table#tablestructure tbody tr:nth-child(1) ul li.primary a',
        ));

        // make assertions for second row
        self::assertStringContainsString(
            'test_column',
            $this->byCssSelector('label[for=checkbox_row_2]')->getText(),
        );

        self::assertSame(
            'varchar(10)',
            $this->getCellByTableId('tablestructure', 2, 4),
        );

        self::assertSame(
            'utf8mb4_general_ci',
            $this->getCellByTableId('tablestructure', 2, 5),
        );

        self::assertSame(
            'Yes',
            $this->getCellByTableId('tablestructure', 2, 7),
        );

        self::assertSame(
            'def',
            $this->getCellByTableId('tablestructure', 2, 8),
        );

        self::assertTrue($this->isElementPresent(
            'cssSelector',
            'table#tablestructure tbody tr:nth-child(2) ul li.primary a',
        ));
    }
}
