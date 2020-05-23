<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for import related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

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
        $this->maximize();
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
        $this->dbQuery("DROP DATABASE test_import1");
        $this->dbQuery("DROP DATABASE test_import2");
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
        $this->dbQuery("CREATE DATABASE " . $this->database_name);
        $this->navigateDatabase($this->database_name);

        $this->_doImport("db");

        $this->dbQuery("USE " . $this->database_name);
        $result = $this->dbQuery("SHOW TABLES");
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
        $this->dbQuery("CREATE DATABASE " . $this->database_name);
        $this->dbQuery("USE " . $this->database_name);
        $this->dbQuery(
            "CREATE TABLE IF NOT EXISTS `test_table` (`val` int(11) NOT NULL)"
        );

        $this->navigateTable('test_table');

        $this->_doImport("table");

        $result = $this->dbQuery("SELECT * FROM test_table");
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
        $this->waitForElement('partialLinkText', "Import")->click();
        $this->waitAjax();
        $this->waitForElement('id', 'input_import_file');

        $this->waitForElement('cssSelector', 'label[for=radio_local_import_file]')->click();

        $this->selectByValue(
            $this->byName("local_import_file"),
            $type . "_import.sql"
        );

        $this->webDriver->wait(5);

        $this->webDriver->executeScript(
            "window.scrollTo(0," .
            $this->byId('buttonGo')->getLocation()->getY()
            . ")"
        );
        $this->webDriver->wait(5);
        $this->scrollToBottom();
        $this->waitUntilElementIsVisible('id', 'buttonGo', 30);

        $this->byId('buttonGo')->click();
        $this->waitUntilElementIsVisible(
            'xpath',
            "//div[@class='success' and contains(., 'Import has been successfully')]",
            30
        );
    }
}
