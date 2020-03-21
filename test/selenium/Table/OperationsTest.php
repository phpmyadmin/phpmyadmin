<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * OperationsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class OperationsTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // MYISAM ENGINE to allow for column-based order selection
        // while table also has a PRIMARY key
        $this->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " `val2` int(11) NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ") ENGINE=MYISAM"
        );
        $this->dbQuery("INSERT INTO test_table (val, val2) VALUES (22, 33)");
        $this->dbQuery("INSERT INTO test_table (val, val2) VALUES (33, 44)");

        $this->login();
        $this->navigateTable('test_table');

        $this->waitAjax();

        $this->expandMore();
        $this->byXPath("//a[contains(., 'Operations')]")->click();

        $this->waitAjax();
        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Table maintenance')]"
        );
        $this->maximize();
    }

    /**
     * Test for changing a table order
     *
     * @return void
     *
     * @group large
     */
    public function testChangeTableOrder()
    {
        $this->selectByLabel(
            $this->byName("order_field"),
            'val'
        );

        $this->byId("order_order_desc")->click();
        $this->byCssSelector(
            "form#alterTableOrderby input[type='submit']"
        )->click();

        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and "
            . "contains(., 'Your SQL query has been executed successfully')]"
        );

        $this->byPartialLinkText("Browse")->click();

        $this->waitAjax();
        $this->waitForElement('cssSelector', "table.table_results");

        $this->assertEquals(
            "2",
            $this->getCellByTableClass('table_results', 1, 5)
        );
    }

    /**
     * Test for moving a table
     *
     * @return void
     *
     * @group large
     */
    public function testMoveTable()
    {
        $this->byCssSelector("form#moveTableForm input[name='new_name']")
            ->sendKeys("2");

        $this->byCssSelector("form#moveTableForm input[type='submit']")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and "
            . "contains(., 'Table `" . $this->database_name
            . "`.`test_table` has been "
            . "moved to `" . $this->database_name . "`.`test_table2`.')]"
        );

        $result = $this->dbQuery("SHOW TABLES");
        $row = $result->fetch_assoc();
        $this->assertEquals(
            "test_table2",
            $row["Tables_in_" . $this->database_name]
        );
    }

    /**
     * Test for renaming a table
     *
     * @return void
     *
     * @group large
     */
    public function testRenameTable()
    {
        $this->byCssSelector("form#tableOptionsForm input[name='new_name']")
            ->sendKeys("2");

        $this->byName("comment")->sendKeys("foobar");

        $this->scrollIntoView('tableOptionsForm');
        $this->waitUntilElementIsVisible('cssSelector', 'form#tableOptionsForm', 30);
        $this->byCssSelector("form#tableOptionsForm input[type='submit']")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and "
            . "contains(., 'Table test_table has been renamed to test_table2')]"
        );

        $result = $this->dbQuery("SHOW TABLES");
        $row = $result->fetch_assoc();
        $this->assertEquals(
            "test_table2",
            $row["Tables_in_" . $this->database_name]
        );
    }

    /**
     * Test for copying a table
     *
     * @return void
     *
     * @group large
     */
    public function testCopyTable()
    {
        $this->scrollIntoView('copyTable');
        $this->waitUntilElementIsVisible('cssSelector', 'form#copyTable', 30);
        $this->byCssSelector("form#copyTable input[name='new_name']")->sendKeys("2");
        $this->byCssSelector("label[for='what_data']")->click();
        $this->byCssSelector("form#copyTable input[type='submit']")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and "
            . "contains(., 'Table `" . $this->database_name
            . "`.`test_table` has been "
            . "copied to `" . $this->database_name . "`.`test_table2`.')]"
        );

        $result = $this->dbQuery("SELECT COUNT(*) as c FROM test_table2");
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
     *
     * @group large
     */
    public function testTruncateTable()
    {
        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'drop_tbl_anchor', 30);
        $this->byId("truncate_tbl_anchor")->click();
        $this->byCssSelector("button.submitOK")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and "
            . "contains(., 'MySQL returned an empty result set')]"
        );

        $result = $this->dbQuery("SELECT COUNT(*) as c FROM test_table");
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
     *
     * @group large
     */
    public function testDropTable()
    {
        $this->reloadPage();
        $dropLink = $this->waitUntilElementIsVisible('partialLinkText', 'Delete the table (DROP)', 30);
        $this->scrollToElement($this->byId('selflink'));
        $dropLink->click();
        $this->byCssSelector("button.submitOK")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and "
            . "contains(., 'MySQL returned an empty result set')]"
        );

        $this->waitForElement(
            'xpath',
            "//a[@class='tabactive' and contains(., 'Structure')]"
        );

        $result = $this->dbQuery("SHOW TABLES");
        $this->assertEquals(
            0,
            $result->num_rows
        );
    }
}
