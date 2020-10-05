<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * StructureTest class
 *
 * @group      selenium
 */
class StructureTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');'
            . 'CREATE TABLE `test_table2` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');'
            . 'INSERT INTO `test_table` (val) VALUES (2);'
        );

        $this->login();
        $this->navigateDatabase($this->databaseName);

        // Let the Database page load
        $this->waitAjax();
        $this->expandMore();
    }

    /**
     * Test for truncating a table
     *
     * @group large
     */
    public function testTruncateTable(): void
    {
        $this->byXPath("(//a[contains(., 'Empty')])[1]")->click();

        $this->waitForElement(
            'cssSelector',
            'button.submitOK'
        )->click();

        $this->assertNotNull(
            $this->waitForElement(
                'xpath',
                "//div[@class='alert alert-success' and contains(., "
                . "'MySQL returned an empty result')]"
            )
        );

        $this->dbQuery(
            'SELECT CONCAT("Count: ", COUNT(*)) as c FROM `' . $this->databaseName . '`.`test_table`',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                // [ ] | Edit | Copy | Delete | 1 | 5
                $this->assertEquals('Count: 0', $this->getCellByTableClass('table_results', 1, 1));
            }
        );
    }

    /**
     * Tests for dropping multiple tables
     *
     * @group large
     */
    public function testDropMultipleTables(): void
    {
        $this->byCssSelector("label[for='tablesForm_checkall']")->click();

        $this->selectByLabel(
            $this->byName('submit_mult'),
            'Drop'
        );

        $this->waitForElement('id', 'buttonYes')
            ->click();

        $this->waitForElement(
            'xpath',
            "//*[contains(., 'No tables found in database')]"
        );

        $this->dbQuery(
            'SHOW TABLES FROM `' . $this->databaseName . '`;',
            function (): void {
                $this->assertFalse($this->isElementPresent('className', 'table_results'));
            }
        );
    }
}
