<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumDbTriggersTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumDbTriggersTest extends PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * Name of database for the test
     *
     * @var string
     */
    private $_dbname;

    /**
     * Helper Object
     *
     * @var Helper
     */
    private $_helper;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $this->_helper = new Helper($this);
        $this->setBrowser($this->_helper->getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
        $this->_helper->dbConnect();
        $this->_dbname = 'pma_db_test';
        $this->_helper->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->_helper->dbQuery('USE ' . $this->_dbname);
        $this->_helper->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );

        $this->_helper->dbQuery(
            "CREATE TABLE `test_table2` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->_helper->dbQuery(
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
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText($this->_dbname)->click();
    }

    /**
     * Creates procedure for tests
     *
     * @return void
     */
    private function _triggerSQL()
    {
        $this->_helper->dbQuery(
            "CREATE TRIGGER `test_trigger` AFTER INSERT ON `test_table` FOR EACH ROW"
            . " UPDATE `" . $this->_dbname . "`.`test_table2` SET val = val + 1"
        );
    }

    /**
     * Create a Trigger
     *
     * @return void
     */
    public function testAddTrigger()
    {
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $ele = $this->_helper->waitForElement("byPartialLinkText", "Triggers");
        $ele->click();

        $ele = $this->_helper->waitForElement("byLinkText", "Add trigger");
        $ele->click();

        $this->_helper->waitForElement("byClassName", "rte_form");

        $this->byName("item_name")->value("test_trigger");

        $this->select($this->byName("item_table"))
            ->selectOptionByLabel("test_table");

        $this->select($this->byName("item_timing"))
            ->selectOptionByLabel("AFTER");

        $this->select($this->byName("item_event"))
            ->selectOptionByLabel("INSERT");

        $proc = "UPDATE " . $this->_dbname . ".`test_table2` SET val=val+1";
        $this->_helper->typeInTextArea($proc);

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Trigger `test_trigger` has been created')]"
        );

        $this->assertTrue(
            $this->_helper->isElementPresent(
                'byXPath',
                "//td[contains(., 'test_trigger')]"
            )
        );

        $result = $this->_helper->dbQuery(
            "SHOW TRIGGERS FROM `" . $this->_dbname . "`;"
        );
        $this->assertEquals(1, $result->num_rows);

        // test trigger
        $this->_helper->dbQuery("INSERT INTO `test_table` (val) VALUES (1);");
        $result = $this->_helper->dbQuery("SELECT val FROM `test_table2`;");
        $row = $result->fetch_assoc();
        $this->assertEquals(3, $row['val']);
    }

    /**
     * Test for editing Triggers
     *
     * @return void
     */
    public function testEditTriggers()
    {
        $this->_triggerSQL();
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $ele = $this->_helper->waitForElement("byPartialLinkText", "Triggers");
        $ele->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//legend[contains(., 'Triggers')]"
        );

        $this->byLinkText("Edit")->click();

        $this->_helper->waitForElement("byClassName", "rte_form");
        $this->_helper->typeInTextArea("0");

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Trigger `test_trigger` has been modified')]"
        );

        // test trigger
        $this->_helper->dbQuery("INSERT INTO `test_table` (val) VALUES (1);");
        $result = $this->_helper->dbQuery("SELECT val FROM `test_table2`;");
        $row = $result->fetch_assoc();
        $this->assertEquals(12, $row['val']);
    }

    /**
     * Test for dropping Trigger
     *
     * @return void
     */
    public function testDropTrigger()
    {
        $this->_triggerSQL();
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $ele = $this->_helper->waitForElement("byPartialLinkText", "Triggers");
        $ele->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//legend[contains(., 'Triggers')]"
        );

        $this->byLinkText("Drop")->click();
        $this->_helper->waitForElement(
            "byXPath", "//button[contains(., 'OK')]"
        )->click();

        $this->_helper->waitForElement("byId", "nothing2display");

        $result = $this->_helper->dbQuery(
            "SHOW TRIGGERS FROM `" . $this->_dbname . "`;"
        );
        $this->assertEquals(0, $result->num_rows);
    }

    /**
     * Tear Down function for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        $this->_helper->dbQuery('DROP DATABASE ' . $this->_dbname);
    }
}
