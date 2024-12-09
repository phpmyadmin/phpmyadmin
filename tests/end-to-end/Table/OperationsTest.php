<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
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
            . 'INSERT INTO test_table (val, val2) VALUES (33, 44);',
        );

        $this->login();
        $this->navigateTable('test_table');

        $this->waitAjax();

        $this->expandMore();
        $this->byXPath("//a[contains(., 'Operations')]")->click();

        $this->waitAjax();
        $this->waitForElement('xpath', "//div[contains(., 'Table maintenance')]");
        $this->reloadPage();
        $this->waitForElement('xpath', "//div[contains(., 'Table maintenance')]");
    }

    /**
     * Test for changing a table order
     */
    public function testChangeTableOrder(): void
    {
        $this->selectByLabel(
            $this->byName('order_field'),
            'val',
        );

        $this->byId('tableOrderDescRadio')->click();
        $this->byCssSelector("form#alterTableOrderby input[type='submit']")->click();

        $this->waitAjax();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Your SQL query has been executed successfully', $success->getText());

        $this->byPartialLinkText('Browse')->click();

        $this->waitAjax();
        $this->waitForElement('cssSelector', 'table.table_results');

        self::assertSame(
            '2',
            $this->getCellByTableClass('table_results', 1, 5),
        );
    }

    /**
     * Test for moving a table
     */
    public function testMoveTable(): void
    {
        $this->byCssSelector("form#moveTableForm input[name='new_name']")
            ->sendKeys('2');

        $this->byCssSelector("form#moveTableForm input[type='submit']")->click();
        $this->waitAjax();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString(
            'Table `' . $this->databaseName . '`.`test_table` has been moved to `'
            . $this->databaseName . '`.`test_table2`',
            $success->getText(),
        );

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES LIKE \'test_table2\'',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('test_table2', $this->getCellByTableClass('table_results', 1, 1));
            },
        );
    }

    /**
     * Test for renaming a table
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

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Table test_table has been renamed to test_table2', $success->getText());

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES LIKE \'test_table2\'',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('test_table2', $this->getCellByTableClass('table_results', 1, 1));
            },
        );
    }

    /**
     * Test for copying a table
     */
    public function testCopyTable(): void
    {
        $this->scrollIntoView('copyTable');
        $this->waitUntilElementIsVisible('cssSelector', 'form#copyTable', 30);
        $this->byCssSelector("form#copyTable input[name='new_name']")->sendKeys('2');
        $this->byCssSelector('label[for="whatRadio2"]')->click();
        $this->waitForElement('cssSelector', 'form#copyTable input[type=\'submit\']')->click();
        $this->waitAjax();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString(
            'Table `' . $this->databaseName . '`.`test_table` has been copied to `'
            . $this->databaseName . '`.`test_table2`',
            $success->getText(),
        );

        $this->dbQuery(
            'SELECT COUNT(*) as c FROM `' . $this->databaseName . '`.test_table2',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('2', $this->getCellByTableClass('table_results', 1, 1));
            },
        );
    }

    /**
     * Test for truncating a table
     */
    public function testTruncateTable(): void
    {
        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'drop_tbl_anchor', 30);
        $this->byId('truncate_tbl_anchor')->click();
        $this->waitForElement('id', 'functionConfirmOkButton')->click();
        $this->waitAjax();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('MySQL returned an empty result set', $success->getText());

        $this->dbQuery(
            'SELECT CONCAT("Count: ", COUNT(*)) as c FROM `' . $this->databaseName . '`.test_table',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('Count: 0', $this->getCellByTableClass('table_results', 1, 1));
            },
        );
    }

    /**
     * Test for dropping a table
     */
    public function testDropTable(): void
    {
        $dropLink = $this->waitUntilElementIsVisible('partialLinkText', 'Delete the table (DROP)', 30);
        $this->scrollToBottom();
        $dropLink->click();
        $this->waitForElement('id', 'functionConfirmOkButton')->click();
        $this->waitAjax();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('MySQL returned an empty result set', $success->getText());

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            },
        );
    }
}
