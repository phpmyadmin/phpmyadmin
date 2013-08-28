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
 * PmaSeleniumTableInsertTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTableInsertTest extends PHPUnit_Extensions_Selenium2TestCase
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
     * Setup the browser environment to run the selenium t
     * est case
     *
     * @return void
     */
    public function setUp()
    {
        $this->_helper = new Helper($this);
        $this->setBrowser($this->_helper->getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
        $this->_helper->dbConnect();
        $this->_dbname = 'pma_db_' . time();
        $this->_helper->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->_helper->dbQuery('USE ' . $this->_dbname);
        $this->_helper->dbQuery(
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
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText($this->_dbname)->click();
        $this->_helper->waitForElement("byLinkText", "test_table");
    }

    /**
     * Insert data into table
     *
     * @return void
     */
    public function testAddData()
    {
        $this->byLinkText("Insert")->click();
        $this->_helper->waitForElement("byId", "insertForm");

        $this->byId("field_1_3")->value("1");
        $this->byId("field_2_3")->value("abcd");
        $this->byId("field_3_3")->value("2011-01-20 02:00:02");
        $this->byId("field_5_3")->value("foo");
        $this->byId("field_6_3")->value("2010-01-20 02:00:02");

        $select = $this->select($this->byName("after_insert"));
        $select->selectOptionByLabel("Insert another new row");

        // post
        $this->byId("buttonYes")->click();
        $ele = $this->_helper->waitForElement("byClassName", "success");
        $this->assertContains("2 rows inserted", $ele->text());

        $this->byId("field_2_3")->value("Abcd");
        $this->byId("field_3_3")->value("2012-01-20 02:00:02");

        // post
        $this->byCssSelector(
            "input[value=Go]"
        )->click();

        $this->_helper->waitForElementNotPresent("byId", "loading_parent");
        $ele = $this->_helper->waitForElement("byClassName", "success");
        $this->assertContains("1 row inserted", $ele->text());
        $this->_assertDataPresent();
    }

    /**
     * Assert various data present in results table
     *
     * @return void
     */
    private function _assertDataPresent()
    {
        $this->byLinkText("Browse")->click();
        $this->_helper->waitForElement("byId", "table_results");

        $this->assertEquals(
            "1",
            $this->_helper->getTable("table_results.1.5")
        );

        $this->assertEquals(
            "abcd",
            $this->_helper->getTable("table_results.1.6")
        );

        $this->assertEquals(
            "2011-01-20 02:00:02",
            $this->_helper->getTable("table_results.1.7")
        );

        $this->assertEquals(
            "2",
            $this->_helper->getTable("table_results.2.5")
        );

        $this->assertEquals(
            "foo",
            $this->_helper->getTable("table_results.2.6")
        );

        $this->assertEquals(
            "2010-01-20 02:00:02",
            $this->_helper->getTable("table_results.2.7")
        );

        $this->assertEquals(
            "3",
            $this->_helper->getTable("table_results.3.5")
        );

        $this->assertEquals(
            "Abcd",
            $this->_helper->getTable("table_results.3.6")
        );

        $this->assertEquals(
            "2012-01-20 02:00:02",
            $this->_helper->getTable("table_results.3.7")
        );
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
