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

use Facebook\WebDriver\WebDriverKeys;
use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * BrowseTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class BrowseTest extends TestBase
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
            . " `name` varchar(20) NOT NULL,"
            . " `datetimefield` datetime NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->dbQuery(
            "INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES"
            . " (1, 'abcd', '2011-01-20 02:00:02'),"
            . " (2, 'foo', '2010-01-20 02:00:02'),"
            . " (3, 'Abcd', '2012-01-20 02:00:02')"
        );

        $this->login();
        $this->navigateTable('test_table');

        $this->waitAjax();
    }

    /**
     * Test sorting of records in browse table
     *
     * @return void
     *
     * @group large
     */
    public function testSortRecords()
    {
        // case 1
        $this->byPartialLinkText("name")->click();
        $this->waitAjax();

        $this->assertEquals(
            "1",
            $this->getCellByTableClass('table_results', 1, 5)
        );

        $this->assertEquals(
            "3",
            $this->getCellByTableClass('table_results', 2, 5)
        );

        $this->assertEquals(
            "2",
            $this->getCellByTableClass('table_results', 3, 5)
        );

        // case 2
        $this->byPartialLinkText("name")->click();
        $this->waitAjax();

        $this->assertEquals(
            "2",
            $this->getCellByTableClass('table_results', 1, 5)
        );

        $this->assertEquals(
            "1",
            $this->getCellByTableClass('table_results', 2, 5)
        );

        $this->assertEquals(
            "3",
            $this->getCellByTableClass('table_results', 3, 5)
        );

        // case 2
        $this->byLinkText("datetimefield")->click();
        $this->waitAjax();

        $this->getCellByTableClass('table_results', 1, 5);
        $this->assertEquals(
            "3",
            $this->getCellByTableClass('table_results', 1, 5)
        );

        $this->assertEquals(
            "1",
            $this->getCellByTableClass('table_results', 2, 5)
        );

        $this->assertEquals(
            "2",
            $this->getCellByTableClass('table_results', 3, 5)
        );

        // case 4
        $this->byPartialLinkText("datetimefield")->click();
        $this->waitAjax();

        $this->assertEquals(
            "2",
            $this->getCellByTableClass('table_results', 1, 5)
        );

        $this->assertEquals(
            "1",
            $this->getCellByTableClass('table_results', 2, 5)
        );

        $this->assertEquals(
            "3",
            $this->getCellByTableClass('table_results', 3, 5)
        );
    }

    /**
     * Test Edit Record
     *
     * @return void
     *
     * @group large
     */
    public function testChangeRecords()
    {
        $ele = $this->byCssSelector(
            "table.table_results tbody tr:nth-child(2) td:nth-child(2)"
        );
        $this->moveto($ele);
        $this->click();

        $this->waitForElement('id', "insertForm");

        $this->waitAjax();
        $this->waitForElement('id', "insertForm");

        $this->assertEquals(
            "2",
            $this->byId("field_1_3")->getAttribute('value')
        );

        $this->assertEquals(
            "foo",
            $this->byId("field_2_3")->getAttribute('value')
        );

        $this->assertEquals(
            "2010-01-20 02:00:02",
            $this->byId("field_3_3")->getAttribute('value')
        );

        $this->byId("field_3_3")->clear();
        $this->byId("field_3_3")->sendKeys("2009-01-2");
        // shorter date to prevent error,
        // automatically gets appended with 00:00:00

        $this->byId("field_2_3")->clear();
        $this->byId("field_2_3")->sendKeys("foobar");

        $this->byId("buttonYes")->click();

        $this->waitAjax();
        $success = $this->waitForElement('className', "success");
        $this->assertStringContainsString("1 row affected", $success->getText());

        $this->assertEquals(
            "foobar",
            $this->getCellByTableClass('table_results', 2, 6)
        );

        $this->assertEquals(
            "2009-01-02 00:00:00",
            $this->getCellByTableClass('table_results', 2, 7)
        );
    }

    /**
     * Test edit record by double click
     *
     * @return void
     *
     * @group large
     */
    public function testChangeRecordsByDoubleClick()
    {
        $element = $this->byCssSelector(
            "table.table_results tbody tr:nth-child(1) td:nth-child(6)"
        );

        $this->moveto($element);
        $this->doubleclick();

        $this->assertEquals(
            $this->waitForElement(
                'xpath',
                "//div[not(contains(@style,'display: none;'))]//textarea[contains(@class, 'edit_box')]"
            )->getAttribute('value'),
            "abcd"
        );

        $this->byCssSelector("textarea.edit_box")->clear();
        $this->byCssSelector("textarea.edit_box")->sendKeys("abcde");

        $this->keys(WebDriverKeys::RETURN_KEY);

        $this->waitAjax();
        $success = $this->waitForElement(
            'cssSelector',
            "span.ajax_notification div.success"
        );
        $this->assertStringContainsString("1 row affected", $success->getText());

        $this->assertEquals(
            "abcde",
            $this->getCellByTableClass('table_results', 1, 6)
        );
    }

    /**
     * Test copy and insert record
     *
     * @return void
     *
     * @group large
     */
    public function testCopyRecords()
    {
        $ele = $this->byCssSelector(
            "table.table_results tbody tr:nth-child(3) td:nth-child(3)"
        );
        $this->moveto($ele);
        $this->click();
        $this->waitForElement('id', "insertForm");

        $this->assertEquals(
            "Abcd",
            $this->byId("field_2_3")->getAttribute('value')
        );

        $this->assertEquals(
            "2012-01-20 02:00:02",
            $this->byId("field_3_3")->getAttribute('value')
        );

        $this->byId("field_2_3")->clear();
        $this->byId("field_2_3")->sendKeys("ABCDEFG");

        $this->byId("field_3_3")->clear();
        $this->byId("field_3_3")->sendKeys("2012-01-02");

        $this->waitForElement('id', "buttonYes")->click();

        $this->waitAjax();
        $success = $this->waitForElement('className', "success");
        $this->assertStringContainsString("1 row inserted", $success->getText());

        $this->assertEquals(
            "ABCDEFG",
            $this->getCellByTableClass('table_results', 4, 6)
        );

        $this->assertEquals(
            "2012-01-02 00:00:00",
            $this->getCellByTableClass('table_results', 4, 7)
        );
    }

    /**
     * Test search table
     *
     * @return void
     *
     * @group large
     */
    public function testSearchRecords()
    {
        $this->expandMore();

        $this->byPartialLinkText("Search")->click();
        $this->waitForElement('id', "tbl_search_form");

        $this->byId("fieldID_1")->sendKeys("abcd");
        $this->selectByLabel(
            $this->byName("criteriaColumnOperators[1]"),
            'LIKE %...%'
        );

        $this->scrollToBottom();
        $elem = $this->waitForElement('cssSelector', ".tblFooters input[name=submit]");
        $this->moveto($elem);
        $elem->click();

        $this->waitAjax();
        $success = $this->waitForElement('className', "success");
        $this->assertStringContainsString("Showing rows", $success->getText());

        $this->assertEquals(
            "1",
            $this->getCellByTableClass('table_results', 1, 5)
        );

        $this->assertEquals(
            "3",
            $this->getCellByTableClass('table_results', 2, 5)
        );
    }

    /**
     * Test delete multiple records
     *
     * @return void
     *
     * @group large
     */
    public function testDeleteRecords()
    {
        $this->byId("id_rows_to_delete1_left")->click();
        $this->byId("id_rows_to_delete2_left")->click();

        $this->byCssSelector("button[value=delete]")->click();
        $this->waitForElement('cssSelector', "fieldset.confirmation");

        $this->byId("buttonYes")->click();

        $this->waitAjax();
        $success = $this->waitForElement('className', "success");
        $this->assertStringContainsString("Showing rows", $success->getText());

        $this->assertFalse(
            $this->isElementPresent(
                'cssSelector',
                "table.table_results tbody tr:nth-child(2)"
            )
        );
    }
}
