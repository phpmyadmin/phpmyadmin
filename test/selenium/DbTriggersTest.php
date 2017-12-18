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
 * DbTriggersTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class DbTriggersTest extends TestBase
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
            "CREATE TABLE `test_table2` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->dbQuery(
            "INSERT INTO `test_table2` (val) VALUES (2);"
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
    private function _triggerSQL()
    {
        $this->dbQuery(
            "CREATE TRIGGER `test_trigger` "
            . "AFTER INSERT ON `test_table` FOR EACH ROW"
            . " UPDATE `" . $this->database_name
            . "`.`test_table2` SET val = val + 1"
        );
    }

    /**
     * Create a Trigger
     *
     * @return void
     *
     * @group large
     */
    public function testAddTrigger()
    {
        $this->expandMore();
        $this->waitForElement("byPartialLinkText", "Triggers")->click();
        $this->waitAjax();

        $this->waitForElement("byPartialLinkText", "Add trigger")->click();
        $this->waitAjax();

        $this->waitForElement("byClassName", "rte_form");

        $this->byName("item_name")->value("test_trigger");

        $this->select($this->byName("item_table"))
            ->selectOptionByLabel("test_table");

        $this->select($this->byName("item_timing"))
            ->selectOptionByLabel("AFTER");

        $this->select($this->byName("item_event"))
            ->selectOptionByLabel("INSERT");

        $proc = "UPDATE " . $this->database_name . ".`test_table2` SET val=val+1";
        $this->typeInTextArea($proc);

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Trigger `test_trigger` has been created')]"
        );

        $this->assertTrue(
            $this->isElementPresent(
                'byXPath',
                "//td[contains(., 'test_trigger')]"
            )
        );

        $result = $this->dbQuery(
            "SHOW TRIGGERS FROM `" . $this->database_name . "`;"
        );
        $this->assertEquals(1, $result->num_rows);

        // test trigger
        $this->dbQuery("INSERT INTO `test_table` (val) VALUES (1);");
        $result = $this->dbQuery("SELECT val FROM `test_table2`;");
        $row = $result->fetch_assoc();
        $this->assertEquals(3, $row['val']);
    }

    /**
     * Test for editing Triggers
     *
     * @return void
     *
     * @group large
     */
    public function testEditTriggers()
    {
        $this->expandMore();

        $this->_triggerSQL();
        $this->waitForElement("byPartialLinkText", "Triggers")->click();
        $this->waitAjax();

        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Triggers')]"
        );

        $this->byPartialLinkText("Edit")->click();

        $this->waitForElement("byClassName", "rte_form");
        $proc = "UPDATE " . $this->database_name . ".`test_table2` SET val=val+10";
        $this->typeInTextArea($proc);

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Trigger `test_trigger` has been modified')]"
        );

        // test trigger
        $this->dbQuery("INSERT INTO `test_table` (val) VALUES (1);");
        $result = $this->dbQuery("SELECT val FROM `test_table2`;");
        $row = $result->fetch_assoc();
        $this->assertEquals(12, $row['val']);
    }

    /**
     * Test for dropping Trigger
     *
     * @return void
     *
     * @group large
     */
    public function testDropTrigger()
    {
        $this->expandMore();

        $this->_triggerSQL();
        $ele = $this->waitForElement("byPartialLinkText", "Triggers");
        $ele->click();

        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Triggers')]"
        );

        $this->byPartialLinkText("Drop")->click();
        $this->waitForElement(
            "byCssSelector", "button.submitOK"
        )->click();

        $this->waitAjaxMessage();

        // test trigger
        $this->dbQuery("INSERT INTO `test_table` (val) VALUES (1);");
        $result = $this->dbQuery("SELECT val FROM `test_table2`;");
        $row = $result->fetch_assoc();
        $this->assertEquals(2, $row['val']);

        $result = $this->dbQuery(
            "SHOW TRIGGERS FROM `" . $this->database_name . "`;"
        );
        $this->assertEquals(0, $result->num_rows);
    }
}
