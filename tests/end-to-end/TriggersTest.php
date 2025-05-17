<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
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
            . 'INSERT INTO `test_table2` (val) VALUES (2);',
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
            },
        );
    }

    /**
     * Create a Trigger
     */
    public function testAddTrigger(): void
    {
        $this->expandMore();
        $this->waitForElement('partialLinkText', 'Triggers')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Create new trigger')->click();
        $this->waitAjax();

        $this->waitForElement('className', 'rte_form');

        $this->byName('item_name')->sendKeys('test_trigger');

        $this->selectByLabel(
            $this->byName('item_table'),
            'test_table',
        );

        $this->selectByLabel(
            $this->byName('item_timing'),
            'AFTER',
        );

        $this->selectByLabel(
            $this->byName('item_event'),
            'INSERT',
        );

        $proc = 'UPDATE ' . $this->databaseName . '.`test_table2` SET val=val+1';
        $this->typeInTextArea($proc);

        $this->byId('triggersEditorModalSaveButton')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Trigger `test_trigger` has been created', $success->getText());

        self::assertTrue(
            $this->isElementPresent(
                'xpath',
                "//td[contains(., 'test_trigger')]",
            ),
        );

        $this->dbQuery(
            'SHOW TRIGGERS FROM `' . $this->databaseName . '`;',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame('test_trigger', $this->getCellByTableClass('table_results', 1, 1));
            },
        );

        // test trigger
        $this->dbQuery('USE `' . $this->databaseName . '`;INSERT INTO `test_table` (val) VALUES (1);');
        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.`test_table2`;',
            function (): void {
                $this->scrollToElement($this->waitForElement('className', 'table_results'), 0, 20);
                // [ ] | Edit | Copy | Delete | 1 | 3
                self::assertSame('3', $this->getCellByTableClass('table_results', 1, 6));
            },
        );
    }

    /**
     * Test for editing Triggers
     */
    public function testEditTriggers(): void
    {
        $this->expandMore();

        $this->triggerSQL();
        $this->waitForElement('partialLinkText', 'Triggers')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'checkAllCheckbox');

        $this->byPartialLinkText('Edit')->click();

        $this->waitForElement('className', 'rte_form');
        $proc = 'UPDATE ' . $this->databaseName . '.`test_table2` SET val=val+10';
        $this->typeInTextArea($proc);

        $this->byId('triggersEditorModalSaveButton')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Trigger `test_trigger` has been modified', $success->getText());

        // test trigger
        $this->dbQuery('USE `' . $this->databaseName . '`;INSERT INTO `test_table` (val) VALUES (1);');
        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.`test_table2`;',
            function (): void {
                $this->scrollToElement($this->waitForElement('className', 'table_results'), 0, 20);
                // [ ] | Edit | Copy | Delete | 1 | 12
                self::assertSame('12', $this->getCellByTableClass('table_results', 1, 6));
            },
        );
    }

    /**
     * Test for dropping Trigger
     */
    public function testDropTrigger(): void
    {
        $this->expandMore();

        $this->triggerSQL();
        $ele = $this->waitForElement('partialLinkText', 'Triggers');
        $ele->click();

        $this->waitForElement('id', 'checkAllCheckbox');

        $this->byPartialLinkText('Drop')->click();
        $this->waitForElement('id', 'functionConfirmOkButton')->click();

        $this->waitAjaxMessage();

        // test trigger
        $this->dbQuery('USE `' . $this->databaseName . '`;INSERT INTO `test_table` (val) VALUES (1);');
        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.`test_table2`;',
            function (): void {
                $this->scrollToElement($this->waitForElement('className', 'table_results'), 0, 20);
                // [ ] | Edit | Copy | Delete | 1 | 2
                self::assertSame('2', $this->getCellByTableClass('table_results', 1, 6));
            },
        );

        $this->dbQuery(
            'SHOW TRIGGERS FROM `' . $this->databaseName . '`;',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            },
        );
    }
}
