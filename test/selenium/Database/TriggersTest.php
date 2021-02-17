<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * TriggersTest class
 *
 * @group      selenium
 */
class TriggersTest extends TestBase
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
            . 'INSERT INTO `test_table2` (val) VALUES (2);'
        );

        $this->login();

        $this->navigateDatabase($this->databaseName);
    }

    /**
     * Creates procedure for tests
     */
    private function triggerSQL(): void
    {
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TRIGGER `test_trigger` '
            . 'AFTER INSERT ON `test_table` FOR EACH ROW'
            . ' UPDATE `' . $this->databaseName
            . '`.`test_table2` SET val = val + 1',
            null,
            function (): void {
                // Do you really want to execute [..]
                $this->acceptAlert();
            }
        );
    }

    /**
     * Create a Trigger
     *
     * @group large
     */
    public function testAddTrigger(): void
    {
        $this->expandMore();
        $this->waitForElement('partialLinkText', 'Triggers')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Add trigger')->click();
        $this->waitAjax();

        $this->waitForElement('className', 'rte_form');

        $this->byName('item_name')->sendKeys('test_trigger');

        $this->selectByLabel(
            $this->byName('item_table'),
            'test_table'
        );

        $this->selectByLabel(
            $this->byName('item_timing'),
            'AFTER'
        );

        $this->selectByLabel(
            $this->byName('item_event'),
            'INSERT'
        );

        $proc = 'UPDATE ' . $this->databaseName . '.`test_table2` SET val=val+1';
        $this->typeInTextArea($proc);

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Trigger `test_trigger` has been created')]"
        );

        $this->assertTrue(
            $this->isElementPresent(
                'xpath',
                "//td[contains(., 'test_trigger')]"
            )
        );

        $this->dbQuery(
            'SHOW TRIGGERS FROM `' . $this->databaseName . '`;',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals('test_trigger', $this->getCellByTableClass('table_results', 1, 1));
            }
        );

        // test trigger
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'INSERT INTO `test_table` (val) VALUES (1);'
        );
        $this->dbQuery(
            'SELECT val FROM `' . $this->databaseName . '`.`test_table2`;',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                // [ ] | Edit | Copy | Delete | 1 | 3
                $this->assertEquals('3', $this->getCellByTableClass('table_results', 1, 5));
            }
        );
    }

    /**
     * Test for editing Triggers
     *
     * @group large
     */
    public function testEditTriggers(): void
    {
        $this->expandMore();

        $this->triggerSQL();
        $this->waitForElement('partialLinkText', 'Triggers')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Triggers')]"
        );

        $this->byPartialLinkText('Edit')->click();

        $this->waitForElement('className', 'rte_form');
        $proc = 'UPDATE ' . $this->databaseName . '.`test_table2` SET val=val+10';
        $this->typeInTextArea($proc);

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Trigger `test_trigger` has been modified')]"
        );

        // test trigger
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'INSERT INTO `test_table` (val) VALUES (1);'
        );
        $this->dbQuery(
            'SELECT val FROM `' . $this->databaseName . '`.`test_table2`;',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                // [ ] | Edit | Copy | Delete | 1 | 12
                $this->assertEquals('12', $this->getCellByTableClass('table_results', 1, 5));
            }
        );
    }

    /**
     * Test for dropping Trigger
     *
     * @group large
     */
    public function testDropTrigger(): void
    {
        $this->expandMore();

        $this->triggerSQL();
        $ele = $this->waitForElement('partialLinkText', 'Triggers');
        $ele->click();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Triggers')]"
        );

        $this->byPartialLinkText('Drop')->click();
        $this->waitForElement(
            'cssSelector',
            'button.submitOK'
        )->click();

        $this->waitAjaxMessage();

        // test trigger
        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'INSERT INTO `test_table` (val) VALUES (1);'
        );
        $this->dbQuery(
            'SELECT val FROM `' . $this->databaseName . '`.`test_table2`;',
            function (): void {
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                // [ ] | Edit | Copy | Delete | 1 | 2
                $this->assertEquals('2', $this->getCellByTableClass('table_results', 1, 5));
            }
        );

        $this->dbQuery(
            'SHOW TRIGGERS FROM `' . $this->databaseName . '`;',
            function (): void {
                $this->assertFalse($this->isElementPresent('className', 'table_results'));
            }
        );
    }
}
