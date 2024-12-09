<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
class ImportTest extends TestBase
{
    /**
     * setUp function
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->login();
    }

    /**
     * Test for server level import
     */
    public function testServerImport(): void
    {
        $this->doImport('server');
        $this->dbQuery(
            'SHOW DATABASES LIKE \'test_import%\'',
            function (): void {
                self::assertSame('test_import1', $this->getCellByTableClass('table_results', 1, 1));
                self::assertSame('test_import2', $this->getCellByTableClass('table_results', 2, 1));
            },
        );

        // clear db
        $this->dbQuery('DROP DATABASE test_import1;DROP DATABASE test_import2;');
    }

    /**
     * Test for db level import
     */
    public function testDbImport(): void
    {
        $this->dbQuery('CREATE DATABASE IF NOT EXISTS `' . $this->databaseName . '`');
        $this->navigateDatabase($this->databaseName);

        $this->doImport('db');

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW TABLES FROM `' . $this->databaseName . '`',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('test_table', $this->getCellByTableClass('table_results', 1, 1));
            },
        );
    }

    /**
     * Test for table level import
     */
    public function testTableImport(): void
    {
        // setup the db
        $this->dbQuery(
            'CREATE DATABASE IF NOT EXISTS `' . $this->databaseName . '`;'
            . 'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL);',
        );

        $this->navigateTable('test_table');

        $this->doImport('table');

        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.test_table',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('8', $this->getCellByTableClass('table_results', 1, 1));
                self::assertSame('9', $this->getCellByTableClass('table_results', 2, 1));
            },
        );
    }

    /**
     * Function that goes to the import page, uploads a file and submit form
     *
     * @param string $type level: server, db or import
     */
    private function doImport(string $type): void
    {
        $this->waitForElement('partialLinkText', 'Import')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'localFileTab')->click();
        $this->waitForElement('id', 'input_import_file');
        $this->selectByValue($this->byName('local_import_file'), $type . '_import.sql');

        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'sql_options', 30);

        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'buttonGo', 30);
        $this->byId('buttonGo')->click();

        $success = $this->waitUntilElementIsVisible('cssSelector', '.alert-success', 30);
        self::assertStringContainsString('Import has been successfully', $success->getText());
    }
}
