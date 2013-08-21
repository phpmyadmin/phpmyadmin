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
 * PmaSeleniumTablesBrowseDataTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTablesBrowseDataTest extends PHPUnit_Extensions_SeleniumTestCase
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
     * @var obj
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
        $this->start();
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
        $this->_helper->dbQuery(
            "INSERT INTO `test_table` (`id`, `name`, `datetimefield`) VALUES"
            . " (1, 'abcd', '2011-01-20 02:00:02'),"
            . " (2, 'foo', '2010-01-20 02:00:02'),"
            . " (3, 'Abcd', '2012-01-20 02:00:02')"
        );
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click('link='. $this->_dbname.'');
        $this->waitForElementPresent("link=test_table");
        $this->click("link=Browse");
        $this->waitForElementPresent("id=table_results");
    }

    /**
     * Test sorting of records in browse table
     * 
     * @return void
     */
    public function testSortRecords()
    {
        // case 1
        $this->click("link=name");
        $this->waitForElementNotPresent("id=loading_parent");
        usleep(100);
        
        $this->assertEquals(
            $this->getTable("table_results.1.4"),
            "1"
        );

        $this->assertEquals(
            $this->getTable("table_results.2.4"),
            "3"
        );

        $this->assertEquals(
            $this->getTable("table_results.3.4"),
            "2"
        );

        // case 2
        $this->click("link=name");
        $this->waitForElementNotPresent("id=loading_parent");
        usleep(100);
        
        $this->assertEquals(
            $this->getTable("table_results.1.4"),
            "2"
        );

        $this->assertEquals(
            $this->getTable("table_results.2.4"),
            "1"
        );

        $this->assertEquals(
            $this->getTable("table_results.3.4"),
            "3"
        );

        // case 2
        $this->click("link=datetimefield");
        $this->waitForElementNotPresent("id=loading_parent");
        usleep(100);
        
        $this->assertEquals(
            $this->getTable("table_results.1.4"),
            "3"
        );

        $this->assertEquals(
            $this->getTable("table_results.2.4"),
            "1"
        );

        $this->assertEquals(
            $this->getTable("table_results.3.4"),
            "2"
        );

        // case 4
        $this->click("link=datetimefield");
        $this->waitForElementNotPresent("id=loading_parent");
        usleep(100);
        
        $this->assertEquals(
            $this->getTable("table_results.1.4"),
            "2"
        );

        $this->assertEquals(
            $this->getTable("table_results.2.4"),
            "1"
        );

        $this->assertEquals(
            $this->getTable("table_results.3.4"),
            "3"
        );
    }

    /**
     * Test Edit Record
     * 
     * @return void
     */
    public function testChangeRecords()
    {
        $this->click("css=table#table_results tr:eq(2) td:eq(1) a");
        $this->waitForElementPresent("css=form#insertForm");
        
        $this->assertEquals(
            $this->getValue("id=field_1_3"),
            "2"
        );

        $this->assertEquals(
            $this->getValue("id=field_2_3"),
            "foo"
        );

        $this->assertEquals(
            $this->getValue("id=field_3_3"),
            "2010-01-20 02:00:02"
        );

        $this->type("id=field_2_3", "foobar");
        $this->type("id=field_3_3", "2009-01-20 02:00:02");

        $this->click("id=buttonYes");
        $this->waitForElementPresent("css=div.success:contains('1 row affected')");

        $this->assertEquals(
            $this->getTable("table_results.2.5"),
            "foobar"
        );

        $this->assertEquals(
            $this->getTable("table_results.2.6"),
            "2009-01-20 02:00:02"
        );
    }

    /**
     * Test edit record by double click
     * 
     * @return void
     */
    public function testChangeRecordsByDoubleClick()
    {
        $this->doubleClick("css=table#table_results tr:eq(1) td:eq(5)");

        $this->assertEquals(
            $this->getValue("css=textarea.edit_box:first"),
            "abcd"
        );

        $this->type("css=textarea.edit_box:first", "abcde");
        usleep(100);
        $this->keyPressNative(10);
        
        $this->waitForElementPresent("css=div.success:contains('1 row affected')");

        $this->assertEquals(
            $this->getTable("table_results.1.5"),
            "abcde"
        );
    }

    /**
     * Test copy and insert record
     * 
     * @return void
     */
    public function testCopyRecords()
    {
        $this->click("css=table#table_results tr:eq(3) td:eq(2) a");
        $this->waitForElementPresent("css=form#insertForm");
        
        $this->assertEquals(
            $this->getValue("id=field_2_3"),
            "Abcd"
        );

        $this->assertEquals(
            $this->getValue("id=field_3_3"),
            "2012-01-20 02:00:02"
        );

        $this->type("id=field_2_3", "ABCDEFG");
        $this->type("id=field_3_3", "2012-01-20 02:05:02");

        $this->click("id=buttonYes");
        $this->waitForElementPresent("css=div.success:contains('1 row inserted')");

        $this->assertEquals(
            $this->getTable("table_results.4.5"),
            "ABCDEFG"
        );

        $this->assertEquals(
            $this->getTable("table_results.4.6"),
            "2012-01-20 02:05:02"
        );
    }

    /**
     * Test search table
     * 
     * @return void
     */
    public function testSearchRecords()
    {
        $this->click("link=Search");
        $this->waitForElementPresent("css=form#tbl_search_form");
        
        $this->type("id=fieldID_1", "abcd");
        $this->select("name=criteriaColumnOperators[1]", "LIKE %...%");

        $this->click("name=submit");
        $this->waitForElementPresent("css=div.success:contains('Showing rows')");

        $this->assertEquals(
            $this->getTable("table_results.1.4"),
            "1"
        );

        $this->assertEquals(
            $this->getTable("table_results.2.4"),
            "3"
        );
    }

    /**
     * Test delete multiple records
     * 
     * @return void
     */
    public function testDeleteRecords()
    {
        $this->click("id=id_rows_to_delete1_left");
        $this->click("id=id_rows_to_delete2_left");

        $this->click("css=button[value=delete]");
        $this->waitForElementPresent("css=fieldset.confirmation");

        $this->click("id=buttonYes");
        $this->waitForElementPresent("css=div.success:contains('Showing rows')");

        $this->assertElementNotPresent("table#table_results tr:eq(2)");
        $this->assertTextNotPresent("foobar");

    }

    /**
     * Teardown for test cases (drops database)
     * 
     * @return void
     */
    public function tearDown()
    {
        $this->_helper->dbQuery('DROP DATABASE ' . $this->_dbname);
    }
}
