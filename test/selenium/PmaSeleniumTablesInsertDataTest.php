<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

/**
 * PmaSeleniumTablesInsertDataTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTablesInsertDataTest extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * Name of database for the test
     *
     * @var string
     */
    private $_dbname;

    /**
     * PmaSeleniumTestCase Object
     *
     * @var obj
     */
    private $_seleniumTasks;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
        $this->start();
        $this->_seleniumTasks = new PmaSeleniumTestCase($this);
        $this->_seleniumTasks->dbConnect();
        $this->_dbname = 'pma_db_' . time();
        $this->_seleniumTasks->dbQuery('CREATE DATABASE ' . $this->_dbname);
        $this->_seleniumTasks->dbQuery('USE ' . $this->_dbname);
        $this->_seleniumTasks->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `name` varchar(20) NOT NULL,"
            . " `datetimefield` datetime NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->_seleniumTasks->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click('link='. $this->_dbname.'');
        $this->waitForElementPresent("link=test_table");
    }

    /**
     * Insert data into table
     *
     * @return void
     */
    public function testAddData()
    {
        $this->click("link=Insert");
        $this->waitForElementPresent("css=form#insertForm");

        $this->type("id=field_1_3", "1");
        $this->type("id=field_2_3", "abcd");
        $this->type("id=field_3_3", "2011-01-20 02:00:02");
        $this->type("id=field_5_3", "foo");
        $this->type("id=field_6_3", "2010-01-20 02:00:02");

        $this->select("name=after_insert", "Insert another new row");

        // post
        $this->click("id=buttonYes");
        $this->waitForElementPresent("css=div.success:contains('2 rows inserted')");

        $this->type("id=field_2_3", "Abcd");
        $this->type("id=field_3_3", "2012-01-20 02:00:02");

        // post
        $this->click("css=table.insertRowTable:first input[value=Go]");
        $this->waitForElementPresent("css=div.success:contains('1 row inserted')");
        $this->_assertDataPresent();
    }

    private function _assertDataPresent()
    {
        $this->click("link=Browse");
        $this->waitForElementPresent("id=table_results");

        $this->assertEquals(
            "1",
            $this->getTable("table_results.1.4")
        );

        $this->assertEquals(
            "abcd",
            $this->getTable("table_results.1.5")

        );

        $this->assertEquals(
            "2011-01-20 02:00:02",
            $this->getTable("table_results.1.6")
        );

        $this->assertEquals(
            "2",
            $this->getTable("table_results.2.4")
        );

        $this->assertEquals(
            "foo",
            $this->getTable("table_results.2.5")
        );

        $this->assertEquals(
            "2010-01-20 02:00:02",
            $this->getTable("table_results.2.6")
        );

        $this->assertEquals(
            "3",
            $this->getTable("table_results.3.4")
        );

        $this->assertEquals(
            "Abcd",
            $this->getTable("table_results.3.5")
        );

        $this->assertEquals(
            "2012-01-20 02:00:02",
            $this->getTable("table_results.3.6")
        );
    }
    public function tearDown()
    {
        $this->_seleniumTasks->dbQuery('DROP DATABASE ' . $this->_dbname);
    }
}
