<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for 'query by example' tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * QueryByExampleTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class QueryByExampleTest extends TestBase
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
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->dbQuery(
            "INSERT INTO `test_table` (val) VALUES (2), (6), (5), (3), (4), (4), (5);"
        );

        $this->maximize();
        $this->login();
    }

    /**
     * Test typing a SQL query on Server SQL page and submitting it
     *
     * @return void
     */
    public function testQueryByExample()
    {
        $this->navigateDatabase($this->database_name);

        $this->waitForElement('partialLinkText', 'Query')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Query by example')->click();
        $this->waitAjax();

        /* Select Columns to be used in the query */
        $this->selectByValue(
            $this->waitForElement('name', 'criteriaColumn[0]'),
            '`test_table`.`id`'
        );

        $this->selectByValue(
            $this->waitForElement('name', 'criteriaColumn[1]'),
            '`test_table`.`val`'
        );

        /* Set aliases for the columns */
        $this->waitForElement('name', 'criteriaAlias[0]')->sendKeys('ID');
        $this->waitForElement('name', 'criteriaAlias[1]')->sendKeys('VAL');

        /* Set Sort orders */
        $this->selectByLabel(
            $this->waitForElement('name', 'criteriaSort[0]'),
            'Descending'
        );

        $this->selectByLabel(
            $this->waitForElement('name', 'criteriaSort[1]'),
            'Ascending'
        );

        /* Select sort order amongst columns */
        $this->selectByValue(
            $this->waitForElement('name', 'criteriaSortOrder[0]'),
            '2'
        );

        $this->selectByValue(
            $this->waitForElement('name', 'criteriaSortOrder[1]'),
            '1'
        );

        /* Set criteria conditions */
        $this->waitForElement('name', 'criteria[0]')->sendKeys('> 1');
        $this->waitForElement('name', 'criteria[1]')->sendKeys('< 6');

        /* Change operator to AND */
        /*
        //TODO: Needs to be re-done
        $radioElements = $this->elements(
            $this->using('css selector')->sendKeys('input[name="criteriaAndOrColumn[0]"]')
        );
        if (count($radioElements) > 2) {
            $radioElements[1]->click();
        }
        */

        $this->scrollToBottom();

        /* Update Query in the editor */
        $this->byCssSelector('input[name=modify]')->click();
        $this->waitAjax();

        $this->scrollToBottom();

        $expected = "SELECT `test_table`.`id` AS `ID`, `test_table`.`val` AS `VAL`"
            . "\nFROM `test_table`"
            . "\nWHERE ((`test_table`.`id` > 1) AND (`test_table`.`val` < 6))"
            . "\nORDER BY `test_table`.`val` ASC, `test_table`.`id` DESC";
        $actual = trim($this->waitForElement('id', 'textSqlquery')->getAttribute('value'));

        /* Compare generated query */
        $this->assertEquals(
            $expected,
            $actual
        );

        $this->scrollToBottom();

        /* Submit the query */
        $this->waitForElement('cssSelector', 'input[value="Submit Query"]')->click();
        $this->waitAjax();

        $this->waitForElement('cssSelector', 'table.table_results');

        /* Assert Row 1 */
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 1, 5)
        );
        $this->assertEquals(
            3,
            $this->getCellByTableClass('table_results', 1, 6)
        );

        /* Assert Row 2 */
        $this->assertEquals(
            6,
            $this->getCellByTableClass('table_results', 2, 5)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 2, 6)
        );

        /* Assert Row 3 */
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 3, 5)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 3, 6)
        );

        /* Assert Row 4 */
        $this->assertEquals(
            7,
            $this->getCellByTableClass('table_results', 4, 5)
        );
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 4, 6)
        );

        /* Assert Row 5 */
        $this->assertEquals(
            3,
            $this->getCellByTableClass('table_results', 5, 5)
        );
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 5, 6)
        );
    }
}
