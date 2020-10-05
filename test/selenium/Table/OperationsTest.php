<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * OperationsTest class
 *
 * @group      selenium
 */
class OperationsTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();

        // MYISAM ENGINE to allow for column-based order selection
        // while table also has a PRIMARY key
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' `val2` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ') ENGINE=MYISAM;'
            . 'INSERT INTO test_table (val, val2) VALUES (22, 33);'
            . 'INSERT INTO test_table (val, val2) VALUES (33, 44);'
        );

        $this->login();
        $this->navigateTable('test_table');

        $this->waitAjax();

        $this->expandMore();
        $this->byXPath("//a[contains(., 'Operations')]")->click();

        $this->waitAjax();
        $this->waitForElement(
            'xpath',
            "//div[contains(., 'Table maintenance')]"
        );
        $this->reloadPage();
        $this->waitForElement(
            'xpath',
            "//div[contains(., 'Table maintenance')]"
        );
    }

    /**
     * Test for changing a table order
     *
     * @group large
     */
    public function testChangeTableOrder(): void
    {
        $this->selectByLabel(
            $this->byName('order_field'),
            'val'
        );

        $this->byId('tableOrderDescRadio')->click();
        $this->byCssSelector(
            "form#alterTableOrderby input[type='submit']"
        )->click();

        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and "
            . "contains(., 'Your SQL query has been executed successfully')]"
        );

        $this->byPartialLinkText('Browse')->click();

        $this->waitAjax();
        $this->waitForElement('cssSelector', 'table.table_results');

        $this->assertEquals(
            '2',
            $this->getCellByTableClass('table_results', 1, 5)
        );
    }

    /**
     * Test for moving a table
     *
     * @group large
     */
    public function testMoveTable(): void
    {
        $this->byCssSelector("form#moveTableForm input[name='new_name']")
            ->sendKeys('2');

        $this->byCssSelector("form#moveTableForm input[type='submit']")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and "
            . "contains(., 'Table `" . $this->databaseName
            . '`.`test_table` has been '
            . 'moved to `' . $this->databaseName . "`.`test_table2`.')]"
        );

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES LIKE \'test_table2\'',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('test_table2', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Test for renaming a table
     *
     * @group large
     */
    public function testRenameTable(): void
    {
        $this->byCssSelector("form#tableOptionsForm input[name='new_name']")
            ->sendKeys('2');

        $this->byName('comment')->sendKeys('foobar');

        $this->scrollIntoView('tableOptionsForm');
        $this->waitUntilElementIsVisible('cssSelector', 'form#tableOptionsForm', 30);
        $this->byCssSelector("form#tableOptionsForm input[type='submit']")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and "
            . "contains(., 'Table test_table has been renamed to test_table2')]"
        );

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES LIKE \'test_table2\'',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('test_table2', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Test for copying a table
     *
     * @group large
     */
    public function testCopyTable(): void
    {
        $this->scrollIntoView('copyTable');
        $this->waitUntilElementIsVisible('cssSelector', 'form#copyTable', 30);
        $this->byCssSelector("form#copyTable input[name='new_name']")->sendKeys('2');
        $this->byCssSelector('label[for="whatRadio2"]')->click();
        $this->byCssSelector("form#copyTable input[type='submit']")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and "
            . "contains(., 'Table `" . $this->databaseName
            . '`.`test_table` has been '
            . 'copied to `' . $this->databaseName . "`.`test_table2`.')]"
        );

        $this->dbQuery(
            'SELECT COUNT(*) as c FROM `' . $this->databaseName . '`.test_table2',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('2', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Test for truncating a table
     *
     * @group large
     */
    public function testTruncateTable(): void
    {
        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'drop_tbl_anchor', 30);
        $this->byId('truncate_tbl_anchor')->click();
        $this->byCssSelector('button.submitOK')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and "
            . "contains(., 'MySQL returned an empty result set')]"
        );

        $this->dbQuery(
            'SELECT CONCAT("Count: ", COUNT(*)) as c FROM `' . $this->databaseName . '`.test_table',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('Count: 0', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Test for dropping a table
     *
     * @group large
     */
    public function testDropTable(): void
    {
        $dropLink = $this->waitUntilElementIsVisible('partialLinkText', 'Delete the table (DROP)', 30);
        $this->scrollToElement($this->byId('selflink'));
        $dropLink->click();
        $this->byCssSelector('button.submitOK')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and "
            . "contains(., 'MySQL returned an empty result set')]"
        );

        $this->waitForElement(
            'xpath',
            "//a[@class='nav-link text-nowrap' and contains(., 'Structure')]"
        );

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES',
            function (): void {
                $this->assertFalse($this->isElementPresent('className', 'table_results'));
            }
        );
    }
}
