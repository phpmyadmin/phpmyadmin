<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * ProceduresTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class ProceduresTest extends TestBase
{
    /**
     * The sql_mode before tests
     *
     * @var int
     */
    private $originalSqlMode = -1;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if ($this->originalSqlMode === -1) {
            $this->originalSqlMode = $this->dbQuery('SELECT @@GLOBAL.SQL_MODE as globalsqm;')->fetch_all(MYSQLI_ASSOC)[0]["globalsqm"];
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
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `name` varchar(20) NOT NULL,"
            . " `datetimefield` datetime NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );

        $this->login();

        $this->navigateDatabase($this->database_name);
        $this->expandMore();
    }

    /**
     * Restore initial state
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->dbQuery(
            "SET GLOBAL sql_mode = '" . $this->originalSqlMode . "';"
        );
        $this->assertEquals($this->originalSqlMode, $this->dbQuery('SELECT @@GLOBAL.SQL_MODE as globalsqm;')->fetch_all(MYSQLI_ASSOC)[0]["globalsqm"]);
        parent::tearDown();
    }

    /**
     * Creates procedure for tests
     *
     * @return void
     */
    private function _procedureSQL()
    {
        $this->dbQuery(
            "CREATE PROCEDURE `test_procedure`(IN `inp` VARCHAR(20), OUT `outp` INT)"
            . " NOT DETERMINISTIC READS SQL DATA SQL SECURITY DEFINER SELECT char_"
            . "length(inp) + count(*) FROM test_table INTO outp"
        );
    }

    /**
     * Create a procedure
     *
     * @return void
     *
     * @group large
     */
    public function testAddProcedure()
    {
        $this->waitForElement('partialLinkText', "Routines")->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', "Add routine")->click();

        $this->waitForElement('className', "rte_form");

        $this->byName("item_name")->sendKeys("test_procedure");

        $this->byName("item_param_name[0]")->sendKeys("inp");
        $this->selectByLabel(
            $this->byName("item_param_type[0]"),
            'VARCHAR'
        );
        $this->byName("item_param_length[0]")->sendKeys("20");

        $this->byCssSelector("input[value='Add parameter']")->click();

        $this->selectByLabel(
            $this->byName("item_param_dir[1]"),
            'OUT'
        );
        $ele = $this->waitForElement('name', "item_param_name[1]");
        $ele->sendKeys("outp");

        $proc = "SELECT char_length(inp) + count(*) FROM test_table INTO outp";
        $this->typeInTextArea($proc);

        $this->selectByLabel(
            $this->byName("item_sqldataaccess"),
            'READS SQL DATA'
        );

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and contains(., "
            . "'Routine `test_procedure` has been created')]"
        );

        $result = $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->database_name . "'"
        );

        $this->assertEquals(1, $result->num_rows);
        $this->_executeProcedure("test_procedure", 14);
    }

    /**
     * Test for editing procedure
     *
     * @return void
     *
     * @group large
     */
    public function testEditProcedure()
    {
        $this->_procedureSQL();
        $this->waitForElement('partialLinkText', "Routines")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Routines')]"
        );

        $this->byPartialLinkText("Edit")->click();
        $this->waitForElement('className', "rte_form");
        $this->byName("item_param_length[0]")->clear();
        $this->byName("item_param_length[0]")->sendKeys("30");

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and contains(., "
            . "'Routine `test_procedure` has been modified')]"
        );

        $this->_executeProcedure("test_procedure", 14);
    }

    /**
     * Test for dropping procedure
     *
     * @return void
     *
     * @group large
     */
    public function testDropProcedure()
    {
        $this->_procedureSQL();
        $this->waitForElement('partialLinkText', "Routines")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Routines')]"
        );

        $this->byPartialLinkText("Drop")->click();
        $this->waitForElement(
            'cssSelector',
            "button.submitOK"
        )->click();

        $this->waitAjaxMessage();

        $result = $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->database_name . "'"
        );
        $this->assertEquals(0, $result->num_rows);
    }

    /**
     * Execute procedure
     *
     * @param string $text   String to pass as inp param
     * @param int    $length Expected output length
     *
     * @return void
     */
    private function _executeProcedure($text, $length)
    {
        $this->waitAjax();
        $this->waitUntilElementIsVisible('partialLinkText', 'Execute', 30)->click();// The space before Execute is because of &nbsp;
        $this->waitUntilElementIsVisible('name', "params[inp]", 30)->sendKeys($text);
        $this->byCssSelector("div.ui-dialog-buttonset button:nth-child(1)")->click();

        $this->waitAjax();
        $this->waitForElement(
            'cssSelector',
            "span#PMA_slidingMessage table tbody"
        );
        $head = $this->byCssSelector("span#PMA_slidingMessage table tbody")->getText();
        $this->assertEquals("outp\n$length", $head);
    }
}
