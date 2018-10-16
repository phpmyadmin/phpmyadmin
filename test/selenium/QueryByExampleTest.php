<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for 'query by example' tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

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
    public function setUp()
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
    }

    /**
     * Test typing a SQL query on Server SQL page and submitting it
     *
     * @return void
     */
    public function testQueryByExample()
    {
        $this->navigateDatabase($this->database_name);

        $this->waitForElement('byPartialLinkText', 'Query')->click();
        $this->waitAjax();

        $this->waitForElement('byPartialLinkText', 'Query by example')->click();
        $this->waitAjax();

        /* Select Columns to be used in the query */
        $select = $this->select(
            $this->waitForElement('byName', 'criteriaColumn[0]')
        );
        $select->selectOptionByValue('`test_table`.`id`');

        $select = $this->select(
            $this->waitForElement('byName', 'criteriaColumn[1]')
        );
        $select->selectOptionByValue('`test_table`.`val`');

        /* Set aliases for the columns */
        $this->waitForElement('byName', 'criteriaAlias[0]')->value('ID');
        $this->waitForElement('byName', 'criteriaAlias[1]')->value('VAL');

        /* Set Sort orders */
        $select = $this->select(
            $this->waitForElement('byName', 'criteriaSort[0]')
        );
        $select->selectOptionByLabel('Descending');

        $select = $this->select(
            $this->waitForElement('byName', 'criteriaSort[1]')
        );
        $select->selectOptionByLabel('Ascending');

        /* Select sort order amongst columns */
        $select = $this->select(
            $this->waitForElement('byName', 'criteriaSortOrder[0]')
        );
        $select->selectOptionByValue('2');

        $select = $this->select(
            $this->waitForElement('byName', 'criteriaSortOrder[1]')
        );
        $select->selectOptionByValue('1');

        /* Set criteria conditions */
        $this->waitForElement('byName', 'criteria[0]')->value('> 1');
        $this->waitForElement('byName', 'criteria[1]')->value('< 6');

        /* Change operator to AND */
        $radioElements = $this->elements(
            $this->using('css selector')->value('input[name="criteriaAndOrColumn[0]"]')
        );
        if (count($radioElements) > 2) {
            $radioElements[1]->click();
        }

        $this->scrollToBottom();

        /* Update Query in the editor */
        $this->byCssSelector('input[name=modify]')->click();
        $this->waitAjax();

        $this->scrollToBottom();

        $expected = "SELECT `test_table`.`id` AS `ID`, `test_table`.`val` AS `VAL`"
            . "\nFROM `test_table`"
            . "\nWHERE ((`test_table`.`id` > 1) AND (`test_table`.`val` < 6))"
            . "\nORDER BY `test_table`.`val` ASC, `test_table`.`id` DESC";
        $actual = trim($this->waitForElement('byId', 'textSqlquery')->value());

        /* Compare generated query */
        $this->assertEquals(
            $expected,
            $actual
        );

        $this->scrollToBottom();

        /* Submit the query */
        $this->waitForElement('byCssSelector', 'input[value="Submit Query"]')->click();
        $this->waitAjax();

        $this->waitForElement('byCssSelector', 'table.table_results');

        /* Assert Row 1 */
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 1, 1)
        );
        $this->assertEquals(
            3,
            $this->getCellByTableClass('table_results', 1, 2)
        );

        /* Assert Row 2 */
        $this->assertEquals(
            6,
            $this->getCellByTableClass('table_results', 2, 1)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 2, 2)
        );

        /* Assert Row 3 */
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 3, 1)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 3, 2)
        );

        /* Assert Row 4 */
        $this->assertEquals(
            7,
            $this->getCellByTableClass('table_results', 4, 1)
        );
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 4, 2)
        );

        /* Assert Row 5 */
        $this->assertEquals(
            3,
            $this->getCellByTableClass('table_results', 5, 1)
        );
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 5, 2)
        );
    }
}
