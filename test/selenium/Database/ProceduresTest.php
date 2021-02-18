<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;
use function sleep;
use function str_replace;

/**
 * ProceduresTest class
 *
 * @group      selenium
 */
class ProceduresTest extends TestBase
{
    /**
     * The sql_mode before tests
     *
     * @var string
     */
    private $originalSqlMode = '';

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
                    $this->originalSqlMode
                ) . "';"
            );
        }

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `name` varchar(20) NOT NULL,'
            . ' `datetimefield` datetime NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');'
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
                $optionsSelector = '//a[contains(., "+ Options")]';
                $fullTextSelector = '//label[contains(., "Full texts")]';
                $this->assertTrue($this->isElementPresent('xpath', $optionsSelector));
                $this->byXPath($optionsSelector)->click();
                $this->waitForElement('xpath', $fullTextSelector);
                sleep(2);// Wait for the animation to display the box
                $this->byXPath($fullTextSelector)->click();
                $this->byCssSelector('.slide-wrapper .tblFooters input[type=submit]')->click();
                $this->waitAjax();
                sleep(2);// Waitfor the new results
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $sqlMode = $this->getCellByTableClass('table_results', 1, 1);
                $this->assertNotEmpty($sqlMode);
            }
        );

        return $sqlMode;
    }

    /**
     * Restore initial state
     */
    protected function tearDown(): void
    {
        if ($this->originalSqlMode !== '') {
            $this->dbQuery(
                "SET GLOBAL sql_mode = '" . $this->originalSqlMode . "';"
            );
            $this->assertEquals(
                $this->originalSqlMode,
                $this->getSqlMode()
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
            . 'length(inp) + count(*) FROM test_table INTO outp'
        );
    }

    /**
     * Create a procedure
     *
     * @group large
     */
    public function testAddProcedure(): void
    {
        $this->waitForElement('partialLinkText', 'Routines')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Add routine')->click();

        $this->waitForElement('className', 'rte_form');

        $this->byName('item_name')->sendKeys('test_procedure');

        $this->byName('item_param_name[0]')->sendKeys('inp');
        $this->selectByLabel(
            $this->byName('item_param_type[0]'),
            'VARCHAR'
        );
        $this->byName('item_param_length[0]')->sendKeys('20');

        $this->byCssSelector("input[value='Add parameter']")->click();

        $this->selectByLabel(
            $this->byName('item_param_dir[1]'),
            'OUT'
        );
        $ele = $this->waitForElement('name', 'item_param_name[1]');
        $ele->sendKeys('outp');

        $proc = 'SELECT char_length(inp) + count(*) FROM test_table INTO outp';
        $this->typeInTextArea($proc);

        $this->selectByLabel(
            $this->byName('item_sqldataaccess'),
            'READS SQL DATA'
        );

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Routine `test_procedure` has been created')]"
        );

        $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->databaseName . "'",
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals($this->databaseName, $this->getCellByTableClass('table_results', 1, 1));
            }
        );

        $this->executeProcedure('test_procedure', 14);
    }

    /**
     * Test for editing procedure
     *
     * @group large
     */
    public function testEditProcedure(): void
    {
        $this->procedureSQL();
        $this->waitForElement('partialLinkText', 'Routines')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Routines')]"
        );

        $this->byPartialLinkText('Edit')->click();
        $this->waitForElement('className', 'rte_form');
        $this->byName('item_param_length[0]')->clear();
        $this->byName('item_param_length[0]')->sendKeys('30');

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Routine `test_procedure` has been modified')]"
        );

        $this->executeProcedure('test_procedure', 14);
    }

    /**
     * Test for dropping procedure
     *
     * @group large
     */
    public function testDropProcedure(): void
    {
        $this->procedureSQL();
        $this->waitForElement('partialLinkText', 'Routines')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Routines')]"
        );

        $this->byPartialLinkText('Drop')->click();
        $this->waitForElement(
            'cssSelector',
            'button.submitOK'
        )->click();

        $this->waitAjaxMessage();

        $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->databaseName . "'",
            function (): void {
                $this->assertFalse($this->isElementPresent('className', 'table_results'));
            }
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
        $this->byCssSelector('div.ui-dialog-buttonset button:nth-child(1)')->click();

        $this->waitAjax();
        $this->waitForElement(
            'cssSelector',
            'span#PMA_slidingMessage table tbody'
        );
        $this->waitUntilElementIsVisible('cssSelector', 'span#PMA_slidingMessage', 30);
        sleep(2);// Give more chances to the JS effect to finish
        $head = $this->byCssSelector('span#PMA_slidingMessage table tbody')->getText();
        $this->assertEquals("outp\n" . $length, $head);
    }
}
