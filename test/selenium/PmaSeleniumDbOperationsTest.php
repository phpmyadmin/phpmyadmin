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
 * PmaSeleniumDbOperationsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumDbOperationsTest extends PHPUnit_Extensions_Selenium2TestCase
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
        $this->_helper->waitForElement("byLinkText", "Operations")->click();
        $this->_helper->waitForElement(
            "byXPath", "//legend[contains(., 'Database comment:')]"
        );
    }

    /**
     * Test for adding database comment
     *
     * @return void
     */
    public function testDbComment()
    {
        $this->byName("comment")->value("comment_foobar");
        $this->byXPath("(//input[@value='Go'])[1]")->click();

        $this->assertNotNull(
            $this->_helper->waitForElement(
                "byXPath",
                "//span[@id='span_table_comment' and contains(., 'comment_foobar')]"
            )
        );
    }

    /**
     * Test for renaming database
     *
     * @return void
     */
    public function testRenameDB()
    {
        $this->byCssSelector("form#rename_db_form input[name=newname]")
            ->value("pma_test_db_renamed");

        $this->byXPath("(//input[@value='Go'])[3]")->click();

        $this->_helper->waitForElement(
            "byXPath", "//button[contains(., 'OK')]"
        )->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: pma_test_db_renamed')]"
        );

        $result = $this->_helper->dbQuery(
            "SHOW DATABASES LIKE 'pma_test_db_renamed';"
        );
        $this->assertEquals(1, $result->num_rows);

        $result = $this->_helper->dbQuery(
            "SHOW DATABASES LIKE '". $this->_dbname ."';"
        );
        $this->assertEquals(0, $result->num_rows);

        $this->_dbname = "pma_test_db_renamed";
    }

    /**
     * Test for copying database
     *
     * @return void
     */
    public function testCopyDb()
    {
        $this->byCssSelector("form#copy_db_form input[name=newname]")
            ->value("pma_test_db_copy");

        $this->byXPath("(//input[@value='Go'])[4]")->click();

        $this->_helper->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Database " . $this->_dbname ." "
            . "has been copied to pma_test_db_copy')]"
        );

        $result = $this->_helper->dbQuery(
            "SHOW DATABASES LIKE 'pma_test_db_copy';"
        );
        $this->assertEquals(1, $result->num_rows);

        $this->_helper->dbQuery("DROP DATABASE pma_test_db_copy");
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
