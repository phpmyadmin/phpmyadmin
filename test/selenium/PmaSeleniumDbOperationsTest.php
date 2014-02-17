<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';


/**
 * PmaSeleniumDbOperationsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PMA_SeleniumDbOperationsTest extends PMA_SeleniumBase
{
    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText($this->_dbname)->click();
        $this->waitForElement("byLinkText", "Operations")->click();
        $this->waitForElement(
            "byXPath", "//legend[contains(., 'Rename database to:')]"
        );
    }

    /**
     * Test for adding database comment
     *
     * @return void
     *
     * @group large
     */
    public function testDbComment()
    {
        $this->byName("comment")->value("comment_foobar");
        $this->byXPath("(//input[@value='Go'])[1]")->click();

        $this->assertNotNull(
            $this->waitForElement(
                "byXPath",
                "//span[@id='span_table_comment' and contains(., 'comment_foobar')]"
            )
        );
    }

    /**
     * Test for renaming database
     *
     * @return void
     *
     * @group large
     */
    public function testRenameDB()
    {
        $this->byCssSelector("form#rename_db_form input[name=newname]")
            ->value("pma_test_db_renamed");

        $this->byXPath("(//input[@value='Go'])[3]")->click();

        $this->waitForElement(
            "byXPath", "//button[contains(., 'OK')]"
        )->click();

        $this->waitForElement(
            "byXPath",
            "//a[@class='item' and contains(., 'Database: pma_test_db_renamed')]"
        );

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE 'pma_test_db_renamed';"
        );
        $this->assertEquals(1, $result->num_rows);

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE '" . $this->_dbname . "';"
        );
        $this->assertEquals(0, $result->num_rows);

        $this->_dbname = "pma_test_db_renamed";
    }

    /**
     * Test for copying database
     *
     * @return void
     *
     * @group large
     */
    public function testCopyDb()
    {
        $this->byCssSelector("form#copy_db_form input[name=newname]")
            ->value("pma_test_db_copy");

        $this->byXPath("(//input[@value='Go'])[4]")->click();

        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Database " . $this->_dbname
            . " has been copied to pma_test_db_copy')]"
        );

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE 'pma_test_db_copy';"
        );
        $this->assertEquals(1, $result->num_rows);

        $this->dbQuery("DROP DATABASE pma_test_db_copy");
    }
}
