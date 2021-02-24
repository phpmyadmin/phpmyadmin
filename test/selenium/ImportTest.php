<?php
/**
 * Selenium TestCase for import related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use function sleep;

/**
 * ImportTest class
 *
 * @group      selenium
 */
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
     *
     * @group large
     */
    public function testServerImport(): void
    {
        $this->doImport('server');
        $this->dbQuery(
            'SHOW DATABASES LIKE \'test_import%\'',
            function (): void {
                $this->assertEquals('test_import1', $this->getCellByTableClass('table_results', 1, 1));
                $this->assertEquals('test_import2', $this->getCellByTableClass('table_results', 2, 1));
            }
        );

        // clear db
        $this->dbQuery(
            'DROP DATABASE test_import1;'
            . 'DROP DATABASE test_import2;'
        );
    }

    /**
     * Test for db level import
     *
     * @group large
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
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('test_table', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Test for table level import
     *
     * @group large
     */
    public function testTableImport(): void
    {
        // setup the db
        $this->dbQuery(
            'CREATE DATABASE IF NOT EXISTS `' . $this->databaseName . '`;'
            . 'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL);'
        );

        $this->navigateTable('test_table');

        $this->doImport('table');

        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.test_table',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('8', $this->getCellByTableClass('table_results', 1, 1));
                $this->assertEquals('9', $this->getCellByTableClass('table_results', 2, 1));
            }
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
        $this->waitForElement('id', 'input_import_file');

        $this->waitForElement('cssSelector', 'label[for=radio_local_import_file]')->click();

        $this->selectByValue(
            $this->byName('local_import_file'),
            $type . '_import.sql'
        );

        $this->webDriver->wait(5);

        $this->webDriver->executeScript(
            'window.scrollTo(0,' .
            $this->byId('buttonGo')->getLocation()->getY()
            . ')'
        );
        $this->webDriver->wait(5);
        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'buttonGo', 30);

        $this->byId('buttonGo')->click();
        sleep(2);
        $this->waitUntilElementIsVisible(
            'xpath',
            "//div[@class='alert alert-success' and contains(., 'Import has been successfully')]",
            30
        );
    }
}
