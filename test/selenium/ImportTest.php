<?php
/**
 * Selenium TestCase for import related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

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
     * @return void
     *
     * @group large
     */
    public function testServerImport()
    {
        $this->doImport('server');
        $this->dbQuery(
            'SHOW DATABASES LIKE \'test_import%\'',
            function () {
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
     * @return void
     *
     * @group large
     */
    public function testDbImport()
    {
        $this->dbQuery('CREATE DATABASE IF NOT EXISTS `' . $this->database_name . '`');
        $this->navigateDatabase($this->database_name);

        $this->doImport('db');

        $this->dbQuery(
            'USE `' . $this->database_name . '`;'
            . 'SHOW TABLES FROM `' . $this->database_name . '`',
            function () {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('test_table', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Test for table level import
     *
     * @return void
     *
     * @group large
     */
    public function testTableImport()
    {
        // setup the db
        $this->dbQuery(
            'CREATE DATABASE IF NOT EXISTS `' . $this->database_name . '`;'
            . 'USE `' . $this->database_name . '`;'
            . 'CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL);'
        );

        $this->navigateTable('test_table');

        $this->doImport('table');

        $this->dbQuery(
            'SELECT * FROM `' . $this->database_name . '`.test_table',
            function () {
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
     *
     * @return void
     */
    private function doImport($type)
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
        $this->waitUntilElementIsVisible(
            'xpath',
            "//div[@class='alert alert-success' and contains(., 'Import has been successfully')]",
            30
        );
    }
}
