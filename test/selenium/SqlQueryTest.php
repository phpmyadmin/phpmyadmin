<?php
/**
 * Selenium TestCase for typing and executing SQL query tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * SqlQueryTest class
 *
 * @group      selenium
 */
class SqlQueryTest extends TestBase
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
            . ' PRIMARY KEY (`id`)'
            . ');'
            . 'INSERT INTO `test_table` (val) VALUES (2), (3), (4), (5);'
        );
        $this->login();
    }

    /**
     * Test typing a SQL query on Server SQL page and submitting it
     */
    public function testServerSqlQuery(): void
    {
        $this->waitForElement('partialLinkText', 'SQL')->click();
        $this->waitAjax();

        $this->typeInTextArea(
            'SET @t1=1, @t2=2, @t3:=4;'
            . 'SELECT 1 as `id`,  @t1, @t2, @t3, @t4 := @t1+@t2+@t3;'
        );
        $this->byId('button_submit_query')->click();
        $this->waitAjax();

        $this->waitForElement('cssSelector', 'table.table_results');
        $this->assertEquals(
            1,
            $this->getCellByTableClass('table_results', 1, 1)
        );
        $this->assertEquals(
            1,
            $this->getCellByTableClass('table_results', 1, 2)
        );
        $this->assertEquals(
            2,
            $this->getCellByTableClass('table_results', 1, 3)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 1, 4)
        );
        $this->assertEquals(
            7,
            $this->getCellByTableClass('table_results', 1, 5)
        );

        // test inline edit button
        $this->assertInlineEdit();
    }

    /**
     * Test typing a SQL query on Database SQL page and submitting it
     */
    public function testDatabaseSqlQuery(): void
    {
        $this->navigateDatabase($this->databaseName);

        $this->waitForElement('partialLinkText', 'SQL')->click();
        $this->waitAjax();

        $this->typeInTextArea('SHOW TABLE STATUS');
        $this->byId('button_submit_query')->click();
        $this->waitAjax();

        $this->waitForElement('cssSelector', 'table.table_results');
        $this->assertEquals(
            'test_table',
            $this->getCellByTableClass('table_results', 1, 1)
        );
        $this->assertEquals(
            'InnoDB',
            $this->getCellByTableClass('table_results', 1, 2)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 1, 5)
        );

        // test inline edit button
        $this->assertInlineEdit();
    }

    /**
     * Test typing a SQL query on Table SQL page and submitting it
     */
    public function testTableSqlQuery(): void
    {
        $this->navigateTable('test_table');

        $this->waitForElement('partialLinkText', 'SQL')->click();
        $this->waitAjax();

        $this->typeInTextArea('SELECT * FROM `test_table` WHERE `val` NOT IN (2, 3);');
        $this->scrollToBottom();
        $this->byId('button_submit_query')->click();
        $this->waitAjax();

        $this->waitForElement('cssSelector', 'table.table_results');
        $this->assertEquals(
            3,
            $this->getCellByTableClass('table_results', 1, 5)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 2, 5)
        );
        $this->assertEquals(
            4,
            $this->getCellByTableClass('table_results', 1, 6)
        );
        $this->assertEquals(
            5,
            $this->getCellByTableClass('table_results', 2, 6)
        );

        // test inline edit button
        $this->assertInlineEdit();
    }

    private function assertInlineEdit(): void
    {
        $this->waitForElement('cssSelector', 'a.inline_edit_sql')->click();
        // empty current query
        $this->typeInTextArea('', 1);

        // type in next sql query
        $this->typeInTextArea('SELECT 1', 1);

        $this->scrollIntoView('sql_query_edit_save');
        $this->byId('sql_query_edit_save')->click();
        $this->waitAjax();

        $this->waitForElement('cssSelector', 'table.table_results');
        $this->assertEquals(
            1,
            $this->getCellByTableClass('table_results', 1, 1)
        );
    }
}
