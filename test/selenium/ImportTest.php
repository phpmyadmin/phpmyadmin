<?php
/**
 * Selenium TestCase for import related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\Remote\RemoteWebElement;

/**
 * ImportTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class ImportTest extends TestBase
{
    /**
     * setUp function
     *
     * @return void
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
        $this->_doImport('server');
        $result = $this->dbQuery("SHOW DATABASES LIKE 'test_import%'");
        $this->assertGreaterThanOrEqual(2, $result->num_rows);

        // clear db
        $this->dbQuery('DROP DATABASE test_import1');
        $this->dbQuery('DROP DATABASE test_import2');
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
        $this->dbQuery('CREATE DATABASE ' . $this->database_name);
        $this->navigateDatabase($this->database_name);

        $this->_doImport('db');

        $this->dbQuery('USE ' . $this->database_name);
        $result = $this->dbQuery('SHOW TABLES');
        $this->assertEquals(1, $result->num_rows);
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
        $this->dbQuery('CREATE DATABASE ' . $this->database_name);
        $this->dbQuery('USE ' . $this->database_name);
        $this->dbQuery(
            'CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL)'
        );

        $this->navigateTable('test_table');

        $this->_doImport('table');

        $result = $this->dbQuery('SELECT * FROM test_table');
        $this->assertEquals(2, $result->num_rows);
    }

    /**
     * Function that goes to the import page, uploads a file and submit form
     *
     * @param string $type level: server, db or import
     *
     * @return void
     */
    private function _doImport($type)
    {
        $this->waitForElement('partialLinkText', 'Import')->click();

        $this->waitAjax();

        $el = $this->waitForElement('id', 'input_import_file');

        if (! ($el instanceof RemoteWebElement)) {
            $this->markTestSkipped('Not support set local file.');
        }

        $el->setFileDetector(new LocalFileDetector())
            ->sendKeys(__DIR__ . '/../test_data/' . $type . '_import.sql');

        $this->waitForElement('id', 'buttonGo')->click();

        $this->waitUntilElementIsVisible(
            'xpath',
            "//div[@class='alert alert-success' and contains(., 'Import has been successfully')]",
            30
        );
    }
}
