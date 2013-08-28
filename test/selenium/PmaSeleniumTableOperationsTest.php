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
 * PmaSeleniumTableOperationsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTableOperationsTest extends PHPUnit_Extensions_Selenium2TestCase
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
            . " `val2` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );
        $this->_helper->dbQuery("INSERT INTO test_table (val) VALUES (22)");
        $this->_helper->dbQuery("INSERT INTO test_table (val) VALUES (33)");
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

        $this->_helper->waitForElement(
            "byXPath",
            "//a[contains(., 'test_table')]"
        )->click();

        $this->_helper->waitForElement("byId", "table_results");
        $more = $this->_helper->waitForElement("byLinkText", "More");
        $this->moveto($more);
        $this->byXPath("//a[contains(., 'Operations')]")->click();
        $this->_helper->waitForElement(
            "byXPath",
            "//legend[contains(., 'Alter table order by')]"
        );
    }

    /**
     * Test for changing a table order
     *
     * @return void
     */
    public function testChangeTableOrder()
    {
        $this->select($this->byName("order_field"))
            ->selectOptionByLabel("val");

        $this->byId("order_order_desc")->click();
        $this->byXPath("(//input[@value='Go'])[1]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and "
            . "contains(., 'Your SQL query has been executed successfully')]"
        );

        $this->byLinkText("Browse")->click();
        $this->_helper->waitForElement("byId", "table_results");

        $this->assertEquals(
            "2",
            $this->_helper->getTable("table_results.1.5")
        );
    }

    /**
     * Test for moving a table
     *
     * @return void
     */
    public function testMoveTable()
    {
        $this->byCssSelector("form#moveTableForm input[name='new_name']")
            ->value("2");

        $this->byXPath("(//input[@value='Go'])[2]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and "
            . "contains(., 'Table `" . $this->_dbname . "`.`test_table` has been "
            . "moved to `" . $this->_dbname . "`.`test_table2`.')]"
        );

        $result = $this->_helper->dbQuery("SHOW TABLES");
        $row = $result->fetch_assoc();
        $this->assertEquals(
            "test_table2",
            $row["Tables_in_" . $this->_dbname]
        );
    }

    /**
     * Test for renaming a table
     *
     * @return void
     */
    public function testRenameTable()
    {
        $this->byCssSelector("form#tableOptionsForm input[name='new_name']")
            ->value("2");

        $this->byName("comment")->value("foobar");

        $this->byXPath("(//input[@value='Go'])[3]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and "
            . "contains(., 'Table test_table has been renamed to test_table2')]"
        );

        $this->assertNotNull(
            $this->_helper->waitForElement(
                "byXPath",
                "//span[@id='span_table_comment' and contains(., 'foobar')]"
            )
        );

        $result = $this->_helper->dbQuery("SHOW TABLES");
        $row = $result->fetch_assoc();
        $this->assertEquals(
            "test_table2",
            $row["Tables_in_" . $this->_dbname]
        );
    }

    /**
     * Test for copying a table
     *
     * @return void
     */
    public function testCopyTable()
    {
        $this->byCssSelector("form#copyTable input[name='new_name']")->value("2");
        $this->byCssSelector("label[for='what_data']")->click();
        $this->byXPath("(//input[@value='Go'])[4]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and "
            . "contains(., 'Table `" . $this->_dbname . "`.`test_table` has been "
            . "copied to `" . $this->_dbname . "`.`test_table2`.')]"
        );

        $result = $this->_helper->dbQuery("SELECT COUNT(*) as c FROM test_table2");
        $row = $result->fetch_assoc();
        $this->assertEquals(
            2,
            $row["c"]
        );
    }

    /**
     * Test for truncating a table
     *
     * @return void
     */
    public function testTruncateTable()
    {
        $this->byId("truncate_tbl_anchor")->click();
        $this->byXPath("//button[contains(., 'OK')]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and "
            . "contains(., 'MySQL returned an empty result set')]"
        );

        $result = $this->_helper->dbQuery("SELECT COUNT(*) as c FROM test_table");
        $row = $result->fetch_assoc();
        $this->assertEquals(
            0,
            $row["c"]
        );
    }

    /**
     * Test for dropping a table
     *
     * @return void
     */
    public function testDropTable()
    {
        $this->byId("drop_tbl_anchor")->click();
        $this->byXPath("//button[contains(., 'OK')]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and "
            . "contains(., 'MySQL returned an empty result set')]"
        );

        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='tabactive' and contains(., 'Structure')]"
        );

        $result = $this->_helper->dbQuery("SHOW TABLES");
        $this->assertEquals(
            0,
            $result->num_rows
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
