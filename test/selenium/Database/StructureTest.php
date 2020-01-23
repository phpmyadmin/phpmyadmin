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
            'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ')'
        );
        $this->dbQuery(
            'CREATE TABLE `test_table2` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ')'
        );
        $this->dbQuery(
            'INSERT INTO `test_table` (val) VALUES (2);'
        );

        $this->login();
        $this->navigateDatabase($this->database_name);

        // Let the Database page load
        $this->waitAjax();
        $this->expandMore();
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

        $result = $this->dbQuery('SELECT count(*) as c FROM test_table');
        $row = $result->fetch_assoc();
        $this->assertEquals(0, $row['c']);
    }

    /**
     * Tests for dropping multiple tables
     *
     * @return void
     *
     * @group large
     */
    public function testDropMultipleTables()
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

        $result = $this->dbQuery('SHOW TABLES;');
        $this->assertEquals(0, $result->num_rows);
    }
}
