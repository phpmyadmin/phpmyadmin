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
 * PmaSeleniumTableCreateTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumTableCreateTest extends PMA_SeleniumBase
{

    public function setUpPage()
    {
        parent::setUpPage();

        $this->login();
        $this->waitForElement('byPartialLinkText','Databases')->click();
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');

        // go to specific database page
        $this->waitForElement("byPartialLinkText", $this->database_name)->click();
    }

    /**
     * Creates a table
     *
     * @return void
     *
     * @group large
     */
    public function testCreateTable()
    {
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');
        $this->waitForElementNotPresent('byId', 'ajax_message_num_1');

        $this->waitForElement('byId', 'create_table_form_minimal');
        $this->byCssSelector(
            "form#create_table_form_minimal input[name=table]"
        )->value("test_table");
        $this->byName("num_fields")->clear();
        $this->byName("num_fields")->value("4");
        $this->byCssSelector('input[value=Go]')->click();

        $this->waitForElementNotPresent('byId', 'ajax_message_num_1');
        $this->waitForElement('byName', 'do_save_data');

        $this->waitForElement('byId', "field_1_6")->click(); // null
        $this->waitForElement('byId', "field_0_8")->click(); // auto increment

        // Do this separately since this opens a dialog
        // Since auto-increment auto sets a PRIMARY key since no key present
        $this->waitForElementNotPresent('byId', 'ajax_message_num_1');
        $this->waitForElement('byXPath', '//button[contains(text(), \'Go\')]')->click();

        // column details
        $column_text_details = array(
            "field_0_1" => "test_id",
            "field_0_3" => "14",
            "field_0_9" => "comm1",
            "field_1_1" => "test_column",
            "field_1_3" => "10",
            "field_1_9" => "comm2",
        );

        foreach ($column_text_details as $field => $val) {
            $this->byId($field)->value($val);
        }

        $column_dropdown_details = array(
            "field_0_5" => "UNSIGNED",
            "field_1_2" => "VARCHAR",
            "field_1_5" => "utf8_general_ci",
            "field_1_4" => "As defined:"
        );

        foreach ($column_dropdown_details as $selector => $value) {
            $this->waitForElement(
                'byXPath',
                '//select[@id=\'' . $selector . '\']//option[contains(text(), \'' . $value . '\')]'
            )->click();
        }

        $this->sleep();
        $this->byName("field_default_value[1]")->value("def");

        $this->scrollToBottom();
        $ele = $this->waitForElement('byName', "do_save_data");
        $this->moveto($ele);
        // post
        $ele->click();
        $this->waitForElement(
            'byCssSelector',
            'li.last.table'
        );

        $this->waitForElementNotPresent('byId', 'ajax_message_num_1');

        $this->waitForElement("byPartialLinkText", "test_table");

        $this->_tableStructureAssertions();
    }

    /**
     * Make assertions for table structure
     *
     * @return void
     */
    private function _tableStructureAssertions()
    {
        $this->gotoHomepage();
        $this->waitForElementNotPresent('byId', 'loading_parent');

        $this->navigateTable('test_table');

        $this->waitForElementNotPresent('byId', 'loading_parent');

        // go to structure page
        $this->waitForElement('byPartialLinkText', "Structure")->click();

        $this->waitForElement("byId", "tablestructure");
        $this->waitForElement('byId', 'table_strucuture_id');

        // make assertions for first row
        $this->assertContains(
            "test_id",
            $this->byCssSelector('label[for=checkbox_row_1]')->text()
        );

        $this->assertEquals(
            "int(14)",
            $this->getCellByTableId('tablestructure', 1, 4)
        );

        $this->assertEquals(
            "UNSIGNED",
            $this->getCellByTableId('tablestructure', 1, 6)
        );

        $this->assertEquals(
            "No",
            $this->getCellByTableId('tablestructure', 1, 7)
        );

        $this->assertEquals(
            "",
            $this->getCellByTableId('tablestructure', 1, 8)
        );
        $this->assertEquals(
            "comm1",
            $this->getCellByTableId('tablestructure', 1, 9)
        );

        $this->assertEquals(
            "AUTO_INCREMENT",
            $this->getCellByTableId('tablestructure', 1, 10)
        );

        $this->assertFalse(
            $this->isElementPresent(
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
            $this->getCellByTableId('tablestructure', 2, 4)
        );

        $this->assertEquals(
            "utf8_general_ci",
            $this->getCellByTableId('tablestructure', 2, 5)
        );

        $this->assertEquals(
            "Yes",
            $this->getCellByTableId('tablestructure', 2, 7)
        );

        $this->assertEquals(
            "def",
            $this->getCellByTableId('tablestructure', 2, 8)
        );

        $this->assertFalse(
            $this->isElementPresent(
                'byCssSelector',
                'css=ul.table-structure-actions:nth-child(2) li.primary a'
            )
        );
    }
}
