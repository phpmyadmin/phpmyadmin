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
 * PmaSeleniumDbEventsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumDbEventsTest extends PHPUnit_Extensions_Selenium2TestCase
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
            "INSERT INTO `test_table` (val) VALUES (2);"
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
    private function _eventSQL()
    {
        $start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $end = date('Y-m-d H:i:s', strtotime('+1 day'));

        $this->_helper->dbQuery(
            "CREATE EVENT `test_event` ON SCHEDULE EVERY 2 MINUTE_SECOND STARTS "
            . "'$start' ENDS '$end' ON COMPLETION NOT PRESERVE ENABLE "
            . "DO UPDATE `". $this->_dbname. "`.`test_table` SET val = val + 1"
        );
    }

    /**
     * Create an event
     *
     * @return void
     */
    public function testAddEvent()
    {
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $ele = $this->_helper->waitForElement("byPartialLinkText", "Events");
        $ele->click();

        $ele = $this->_helper->waitForElement("byLinkText", "Add event");
        $ele->click();

        $this->_helper->waitForElement("byClassName", "rte_form");

        $this->byName("item_name")->value("test_event");

        $this->select($this->byName("item_type"))
            ->selectOptionByLabel("RECURRING");

        $this->byName("item_interval_value")->value("1");

        $this->select($this->byName("item_interval_field"))
            ->selectOptionByLabel("MINUTE_SECOND");

        $this->byName("item_starts")
            ->value(date('Y-m-d H:i:s', strtotime('-1 day')));

        $this->byName("item_ends")
            ->value(date('Y-m-d H:i:s', strtotime('+1 day')));

        $proc = "UPDATE " . $this->_dbname . ".`test_table` SET val=val+1";
        $this->_helper->typeInTextArea($proc);

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Event `test_event` has been created')]"
        );

        $this->assertTrue(
            $this->_helper->isElementPresent(
                'byXPath',
                "//td[contains(., 'test_event')]"
            )
        );

        $result = $this->_helper->dbQuery(
            "SHOW EVENTS WHERE Db='" . $this->_dbname . "' AND Name='test_event'"
        );
        $this->assertEquals(1, $result->num_rows);

        usleep(2000000);
        $result = $this->_helper->dbQuery(
            "SELECT val FROM `" . $this->_dbname . "`.`test_table`"
        );
        $row = $result->fetch_assoc();
        $this->assertGreaterThan(2, $row['val']);
    }

    /**
     * Test for editing events
     *
     * @return void
     */
    public function testEditEvents()
    {
        $this->_eventSQL();
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $ele = $this->_helper->waitForElement("byPartialLinkText", "Events");
        $ele->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//legend[contains(., 'Events')]"
        );

        $this->byLinkText("Edit")->click();

        $this->_helper->waitForElement("byClassName", "rte_form");
        $this->byName("item_interval_value")->clear();
        $this->byName("item_interval_value")->value("1");
        $this->_helper->typeInTextArea("00");

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $ele = $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., "
            . "'Event `test_event` has been modified')]"
        );

        usleep(2000000);
        $result = $this->_helper->dbQuery(
            "SELECT val FROM `" . $this->_dbname . "`.`test_table`"
        );
        $row = $result->fetch_assoc();
        $this->assertGreaterThan(100, $row['val']);
    }

    /**
     * Test for dropping event
     *
     * @return void
     */
    public function testDropEvent()
    {
        $this->_eventSQL();
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $ele = $this->_helper->waitForElement("byPartialLinkText", "Events");
        $ele->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//legend[contains(., 'Events')]"
        );

        $this->byLinkText("Drop")->click();
        $this->_helper->waitForElement(
            "byXPath", "//button[contains(., 'OK')]"
        )->click();

        $this->_helper->waitForElement("byId", "nothing2display");

        usleep(1000000);
        $result = $this->_helper->dbQuery(
            "SHOW EVENTS WHERE Db='" . $this->_dbname . "' AND Name='test_event'"
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
