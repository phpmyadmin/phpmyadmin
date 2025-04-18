<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

use function sleep;
use function str_replace;

#[CoversNothing]
#[Large]
class ProceduresTest extends TestBase
{
    /**
     * The sql_mode before tests
     */
    private string $originalSqlMode = '';

    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->originalSqlMode === '') {
            $this->originalSqlMode = $this->getSqlMode();
            $this->dbQuery(
                "SET GLOBAL sql_mode = '" .
                str_replace(
                    'STRICT_TRANS_TABLES',
                    '',
                    $this->originalSqlMode,
                ) . "';",
            );
        }

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `name` varchar(20) NOT NULL,'
            . ' `datetimefield` datetime NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');',
        );

        $this->login();

        $this->navigateDatabase($this->databaseName);
        $this->expandMore();
    }

    private function getSqlMode(): string
    {
        $sqlMode = '';
        $this->dbQuery(
            'SELECT @@GLOBAL.SQL_MODE as globalsqm;',
            function () use (&$sqlMode): void {
                $this->scrollIntoView('extraOptionsButton');
                $this->waitForElement('id', 'extraOptionsButton')->click();
                $this->waitForElement('cssSelector', '#extraOptions.collapse.show');
                $this->waitForElement('xpath', '//label[contains(., "Full texts")]')->click();
                $this->waitForElement('cssSelector', '.collapse .card-footer input[type=submit]')->click();
                $this->waitAjax();
                sleep(2);// Waitfor the new results
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                $sqlMode = $this->getCellByTableClass('table_results', 1, 1);
                self::assertNotEmpty($sqlMode);
            },
        );

        return $sqlMode;
    }

    /**
     * Restore initial state
     */
    protected function tearDown(): void
    {
        if ($this->originalSqlMode !== '') {
            $this->dbQuery("SET GLOBAL sql_mode = '" . $this->originalSqlMode . "';");
            self::assertSame(
                $this->originalSqlMode,
                $this->getSqlMode(),
            );
        }

        parent::tearDown();
    }

    /**
     * Creates procedure for tests
     */
    private function procedureSQL(): void
    {
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE PROCEDURE `test_procedure`(IN `inp` VARCHAR(20), OUT `outp` INT)'
            . ' NOT DETERMINISTIC READS SQL DATA SQL SECURITY DEFINER SELECT char_'
            . 'length(inp) + count(*) FROM test_table INTO outp',
        );
    }

    /**
     * Create a procedure
     */
    public function testAddProcedure(): void
    {
        $this->waitForElement('partialLinkText', 'Routines')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Create new routine')->click();

        $this->waitForElement('className', 'rte_form');

        $this->byName('item_name')->sendKeys('test_procedure');

        $this->byName('item_param_name[0]')->sendKeys('inp');
        $this->selectByLabel(
            $this->byName('item_param_type[0]'),
            'VARCHAR',
        );
        $this->byName('item_param_length[0]')->sendKeys('20');

        $this->byId('addRoutineParameterButton')->click();

        $this->selectByLabel(
            $this->byName('item_param_dir[1]'),
            'OUT',
        );
        $ele = $this->waitForElement('name', 'item_param_name[1]');
        $ele->sendKeys('outp');

        $proc = 'SELECT char_length(inp) + count(*) FROM test_table INTO outp';
        $this->typeInTextArea($proc);

        $this->selectByLabel(
            $this->byName('item_sqldataaccess'),
            'READS SQL DATA',
        );

        $this->byId('routinesEditorModalSaveButton')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Routine `test_procedure` has been created', $success->getText());

        $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->databaseName . "'",
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame($this->databaseName, $this->getCellByTableClass('table_results', 1, 1));
            },
        );

        $this->executeProcedure('test_procedure', 14);
    }

    /**
     * Test for editing procedure
     */
    public function testEditProcedure(): void
    {
        $this->procedureSQL();
        $this->waitForElement('partialLinkText', 'Routines')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'checkAllCheckbox');

        $this->byPartialLinkText('Edit')->click();
        $this->waitForElement('className', 'rte_form');
        $this->byName('item_param_length[0]')->clear();
        $this->byName('item_param_length[0]')->sendKeys('30');

        $this->byId('routinesEditorModalSaveButton')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Routine `test_procedure` has been modified', $success->getText());

        $this->executeProcedure('test_procedure', 14);
    }

    /**
     * Test for dropping procedure
     */
    public function testDropProcedure(): void
    {
        $this->procedureSQL();
        $this->waitForElement('partialLinkText', 'Routines')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'checkAllCheckbox');

        $this->byPartialLinkText('Drop')->click();
        $this->waitForElement('id', 'functionConfirmOkButton')->click();

        $this->waitAjaxMessage();

        $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->databaseName . "'",
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            },
        );
    }

    /**
     * Execute procedure
     *
     * @param string $text   String to pass as inp param
     * @param int    $length Expected output length
     */
    private function executeProcedure(string $text, int $length): void
    {
        $this->waitAjax();
        $this->waitUntilElementIsVisible('partialLinkText', 'Execute', 30)->click();
        $this->waitUntilElementIsVisible('name', 'params[inp]', 30)->sendKeys($text);
        $this->byId('routinesExecuteModalExecuteButton')->click();

        $this->waitAjax();
        $this->waitForElement('cssSelector', 'span#PMA_slidingMessage table tbody');
        $this->waitUntilElementIsVisible('cssSelector', 'span#PMA_slidingMessage', 30);
        sleep(2);// Give more chances to the JS effect to finish
        $head = $this->byCssSelector('span#PMA_slidingMessage table tbody')->getText();
        self::assertSame("outp\n" . $length, $head);
    }
}
