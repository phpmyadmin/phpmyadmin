<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;
use function sleep;

/**
 * CreateTest class
 *
 * @group      selenium
 */
class CreateTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->login();
        $this->waitForElement('partialLinkText', 'Databases')->click();
        $this->waitAjax();

        // go to specific database page
        $this->waitForElement('partialLinkText', $this->databaseName)->click();
    }

    /**
     * Creates a table
     *
     * @group large
     */
    public function testCreateTable(): void
    {
        $this->waitAjax();
        $this->waitAjax();

        $this->waitForElement('id', 'create_table_form_minimal');
        $this->byCssSelector(
            'form#create_table_form_minimal input[name=table]'
        )->sendKeys('test_table');
        $this->byName('num_fields')->clear();
        $this->byName('num_fields')->sendKeys('4');
        $this->byCssSelector('input[value=Go]')->click();

        $this->waitAjax();
        $this->waitForElement('name', 'do_save_data');

        $this->waitForElement('id', 'field_1_7')->click(); // null
        $this->waitForElement('id', 'field_0_9')->click(); // auto increment

        // column details
        $column_text_details = [
            'field_0_1' => 'test_id',
            'field_0_3' => '14',
            'field_0_10' => 'comm1',
            'field_1_1' => 'test_column',
            'field_1_3' => '10',
            'field_1_10' => 'comm2',
        ];

        foreach ($column_text_details as $field => $val) {
            $this->byId($field)->sendKeys($val);
        }

        $column_dropdown_details = [
            'field_0_6' => 'UNSIGNED',
            'field_1_2' => 'VARCHAR',
            'field_1_5' => 'utf8_general_ci',
            'field_1_4' => 'As defined:',
        ];

        foreach ($column_dropdown_details as $selector => $value) {
            $this->waitForElement(
                'xpath',
                '//select[@id=\'' . $selector . '\']//option[contains(text(), \'' . $value . '\')]'
            )->click();
        }

        $this->byName('field_default_value[1]')->sendKeys('def');

        $this->scrollToBottom();
        $ele = $this->waitForElement('name', 'do_save_data');
        $this->moveto($ele);
        // post
        $ele->click();
        $this->waitForElement(
            'cssSelector',
            'li.last.table'
        );

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
        $this->assertStringContainsString(
            'test_id',
            $this->byCssSelector('label[for=checkbox_row_1]')->getText()
        );

        $this->assertEquals(
            'int(14)',
            $this->getCellByTableId('tablestructure', 1, 4)
        );

        $this->assertEquals(
            'UNSIGNED',
            $this->getCellByTableId('tablestructure', 1, 6)
        );

        $this->assertEquals(
            'No',
            $this->getCellByTableId('tablestructure', 1, 7)
        );

        $this->assertEquals(
            'None',
            $this->getCellByTableId('tablestructure', 1, 8)
        );
        $this->assertEquals(
            'comm1',
            $this->getCellByTableId('tablestructure', 1, 9)
        );

        $this->assertEquals(
            'AUTO_INCREMENT',
            $this->getCellByTableId('tablestructure', 1, 10)
        );

        $this->assertFalse(
            $this->isElementPresent(
                'cssSelector',
                'table#tablestructure tbody tr:nth-child(1) "
                . "ul.table-structure-actions li.primary a'
            )
        );

        // make assertions for second row
        $this->assertStringContainsString(
            'test_column',
            $this->byCssSelector('label[for=checkbox_row_2]')->getText()
        );

        $this->assertEquals(
            'varchar(10)',
            $this->getCellByTableId('tablestructure', 2, 4)
        );

        $this->assertEquals(
            'utf8_general_ci',
            $this->getCellByTableId('tablestructure', 2, 5)
        );

        $this->assertEquals(
            'Yes',
            $this->getCellByTableId('tablestructure', 2, 7)
        );

        $this->assertEquals(
            'def',
            $this->getCellByTableId('tablestructure', 2, 8)
        );

        $this->assertFalse(
            $this->isElementPresent(
                'cssSelector',
                'css=ul.table-structure-actions:nth-child(2) li.primary a'
            )
        );
    }
}
