<?php
/**
 * Selenium TestCase for tracking related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * TrackingTest class
 *
 * @group      selenium
 */
class TrackingTest extends TestBase
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
            . 'CREATE TABLE `test_table_2` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');'
            . 'INSERT INTO `test_table` (val) VALUES (2), (3);'
        );

        $this->login();
        $this->skipIfNotPMADB();

        $this->navigateDatabase($this->databaseName);
        $this->expandMore();

        $this->waitForElement('partialLinkText', 'Tracking')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Track table');
        $this->byXPath("(//a[contains(., 'Track table')])[1]")->click();

        $this->waitAjax();
        $this->waitForElement('name', 'delete')->click();
        $this->byCssSelector("input[value='Create version']")->click();
        $this->waitForElement('id', 'versions');
    }

    /**
     * Tests basic tracking functionality
     *
     * @group large
     */
    public function testTrackingData(): void
    {
        $this->executeSqlAndReturnToTableTracking();

        $this->byPartialLinkText('Tracking report')->click();
        $this->waitForElement(
            'xpath',
            "//h3[contains(., 'Tracking report')]"
        );

        $this->assertStringContainsString(
            'DROP TABLE IF EXISTS `test_table`',
            $this->getCellByTableId('ddl_versions', 1, 4)
        );

        $this->assertStringContainsString(
            'CREATE TABLE `test_table` (',
            $this->getCellByTableId('ddl_versions', 2, 4)
        );

        $this->assertStringContainsString(
            'UPDATE test_table SET val = val + 1',
            $this->getCellByTableId('dml_versions', 1, 4)
        );

        $this->assertStringNotContainsString(
            'DELETE FROM test_table WHERE val = 3',
            $this->byId('dml_versions')->getText()
        );

        // only structure
        $this->selectByLabel(
            $this->byName('logtype'),
            'Structure only'
        );

        $this->byCssSelector("input[value='Go']")->click();

        $this->waitAjax();

        $this->assertFalse(
            $this->isElementPresent('id', 'dml_versions')
        );

        $this->assertStringContainsString(
            'DROP TABLE IF EXISTS `test_table`',
            $this->getCellByTableId('ddl_versions', 1, 4)
        );

        $this->assertStringContainsString(
            'CREATE TABLE `test_table` (',
            $this->getCellByTableId('ddl_versions', 2, 4)
        );

        // only data
        $this->selectByLabel(
            $this->waitForElement('name', 'logtype'),
            'Data only'
        );

        $this->byCssSelector("input[value='Go']")->click();

        $this->waitAjax();

        $this->assertFalse(
            $this->isElementPresent('id', 'ddl_versions')
        );

        $this->assertStringContainsString(
            'UPDATE test_table SET val = val + 1',
            $this->getCellByTableId('dml_versions', 1, 4)
        );

        $this->assertStringNotContainsString(
            'DELETE FROM test_table WHERE val = 3',
            $this->byId('dml_versions')->getText()
        );
    }

    /**
     * Tests deactivation of tracking
     *
     * @group large
     */
    public function testDeactivateTracking(): void
    {
        $this->byCssSelector("input[value='Deactivate now']")->click();
        $this->waitForElement(
            'cssSelector',
            "input[value='Activate now']"
        );
        $this->executeSqlAndReturnToTableTracking();
        $this->assertFalse(
            $this->isElementPresent('id', 'dml_versions')
        );
    }

    /**
     * Tests dropping a tracking
     *
     * @group large
     */
    public function testDropTracking(): void
    {
        $this->navigateDatabase($this->databaseName, true);
        $this->expandMore();

        $this->byPartialLinkText('Tracking')->click();

        $this->waitAjax();
        $this->waitForElement('id', 'versions');

        $ele = $this->waitForElement(
            'cssSelector',
            'table#versions tbody tr:nth-child(1) td:nth-child(7) a'
        );
        $this->moveto($ele);
        $this->click();

        $this->waitForElement(
            'cssSelector',
            'button.submitOK'
        )->click();

        $this->waitAjax();
        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Tracking data deleted successfully.')]"
        );

        // Can not use getCellByTableId,
        // since this is under 'th' and not 'td'
        $this->assertStringContainsString(
            'test_table',
            $this->waitForElement(
                'cssSelector',
                'table#noversions tbody tr:nth-child(1) th:nth-child(2)'
            )->getText()
        );
        $this->assertStringContainsString(
            'test_table_2',
            $this->waitForElement(
                'cssSelector',
                'table#noversions tbody tr:nth-child(2) th:nth-child(2)'
            )->getText()
        );
    }

    /**
     * Tests structure snapshot of a tracking
     *
     * @group large
     */
    public function testStructureSnapshot(): void
    {
        $this->byPartialLinkText('Structure snapshot')->click();
        $this->waitForElement('id', 'tablestructure');

        $this->assertStringContainsString(
            'id',
            $this->getCellByTableId('tablestructure', 1, 2)
        );

        $this->assertStringContainsString(
            'val',
            $this->getCellByTableId('tablestructure', 2, 2)
        );

        $this->assertStringContainsString(
            'PRIMARY',
            $this->getCellByTableId('tablestructure_indexes', 1, 1)
        );

        $this->assertStringContainsString(
            'id',
            $this->getCellByTableId('tablestructure_indexes', 1, 5)
        );
    }

    /**
     * Goes to SQL tab, executes queries, returns to tracking page
     */
    private function executeSqlAndReturnToTableTracking(): void
    {
        $this->byPartialLinkText('SQL')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'queryfieldscontainer');
        $this->typeInTextArea(
            ';UPDATE test_table SET val = val + 1; '
            . 'DELETE FROM test_table WHERE val = 3'
        );
        $this->scrollToBottom();
        $this->byCssSelector("input[value='Go']")->click();
        $this->waitAjax();
        $this->waitForElement('className', 'alert-success');

        $this->expandMore();
        $this->byPartialLinkText('Tracking')->click();
        $this->waitAjax();
        $this->waitForElement('id', 'versions');
    }
}
