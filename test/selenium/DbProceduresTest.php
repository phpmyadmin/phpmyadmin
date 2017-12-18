<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * DbProceduresTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class DbProceduresTest extends TestBase
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
            . " `name` varchar(20) NOT NULL,"
            . " `datetimefield` datetime NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
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

        $this->navigateDatabase($this->database_name);
        $this->expandMore();
    }

    /**
     * Creates procedure for tests
     *
     * @return void
     */
    private function _procedureSQL()
    {
        $this->dbQuery(
            "CREATE PROCEDURE `test_procedure`(IN `inp` VARCHAR(10), OUT `outp` INT)"
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
        $this->waitForElement("byPartialLinkText", "Routines")->click();
        $this->waitAjax();

        $this->waitForElement("byPartialLinkText", "Add routine")->click();

        $this->waitForElement("byClassName", "rte_form");

        $this->byName("item_name")->value("test_procedure");

        $this->byName("item_param_name[0]")->value("inp");
        $this->select(
            $this->byName("item_param_type[0]")
        )->selectOptionByLabel("VARCHAR");
        $this->byName("item_param_length[0]")->value("10");

        $this->byCssSelector("input[value='Add parameter']")->click();

        $this->select(
            $this->byName("item_param_dir[1]")
        )->selectOptionByLabel("OUT");
        $ele = $this->waitForElement("byName", "item_param_name[1]");
        $ele->value("outp");

        $proc = "SELECT char_length(inp) + count(*) FROM test_table INTO outp";
        $this->typeInTextArea($proc);

        $this->select(
            $this->byName("item_sqldataaccess")
        )->selectOptionByLabel("READS SQL DATA");

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Routine `test_procedure` has been created')]"
        );

        $result = $this->dbQuery(
            "SHOW PROCEDURE STATUS WHERE Db='" . $this->database_name . "'"
        );

        $this->assertEquals(1, $result->num_rows);
        $this->_executeProcedure("test_procedure", 10);
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
        $this->waitForElement("byPartialLinkText", "Routines")->click();
        $this->waitAjax();

        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Routines')]"
        );

        $this->byPartialLinkText("Edit")->click();
        $this->waitForElement("byClassName", "rte_form");
        $this->byName("item_param_length[0]")->clear();
        $this->byName("item_param_length[0]")->value("12");

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Routine `test_procedure` has been modified')]"
        );

        $this->_executeProcedure("test_procedure", 12);
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
        $this->waitForElement("byPartialLinkText", "Routines")->click();
        $this->waitAjax();

        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Routines')]"
        );

        $this->byPartialLinkText("Drop")->click();
        $this->waitForElement(
            "byCssSelector", "button.submitOK"
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
        $this->waitForElement("byPartialLinkText", "Execute")->click();
        $this->waitForElement("byName", "params[inp]")->value($text);
        $this->byCssSelector("div.ui-dialog-buttonset button:nth-child(1)")->click();

        $this->waitAjax();
        $this->waitForElement(
            "byCssSelector",
            "span#PMA_slidingMessage table tbody"
        );
        $head = $this->byCssSelector("span#PMA_slidingMessage table tbody")->text();
        $this->assertEquals("outp\n$length", $head);
    }
}
