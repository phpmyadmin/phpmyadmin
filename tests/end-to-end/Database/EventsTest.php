<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Large;

use function date;
use function sleep;
use function strtotime;

#[CoversNothing]
#[Large]
class EventsTest extends TestBase
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
            . 'INSERT INTO `test_table` (val) VALUES (2);'
            . 'SET GLOBAL event_scheduler="ON";',
        );
        $this->login();
        $this->navigateDatabase($this->databaseName);

        // Let the Database page load
        $this->waitAjax();
        $this->expandMore();
    }

    /**
     * Tear Down function for test cases
     */
    protected function tearDown(): void
    {
        $this->dbQuery('SET GLOBAL event_scheduler="OFF"');

        parent::tearDown();
    }

    /**
     * Creates procedure for tests
     */
    private function eventSQL(): void
    {
        $start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $end = date('Y-m-d H:i:s', strtotime('+1 day'));

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE EVENT `test_event` ON SCHEDULE EVERY 1 MINUTE_SECOND STARTS '
            . "'" . $start . "' ENDS '" . $end . "' ON COMPLETION NOT PRESERVE ENABLE "
            . 'DO UPDATE `' . $this->databaseName
            . '`.`test_table` SET val = val + 1',
            null,
            function (): void {
                // Do you really want to execute [..]
                $this->acceptAlert();
            },
        );
    }

    /**
     * Create an event
     */
    public function testAddEvent(): void
    {
        $this->waitForElement('partialLinkText', 'Events')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Create new event')->click();
        $this->waitAjax();

        $this->waitForElement('className', 'rte_form');

        $this->selectByLabel($this->byName('item_type'), 'RECURRING');

        $this->byName('item_name')->sendKeys('test_event');
        $this->selectByLabel(
            $this->byName('item_interval_field'),
            'MINUTE_SECOND',
        );

        $this->byName('item_starts')->click()->clear()->sendKeys(date('Y-m-d', strtotime('-1 day')) . ' 00:00:00');

        $this->byName('item_ends')->click()->clear()->sendKeys(date('Y-m-d', strtotime('+1 day')) . ' 00:00:00');

        $this->waitForElement('name', 'item_interval_value')->click()->clear()->sendKeys('1');

        $proc = 'UPDATE ' . $this->databaseName . '.`test_table` SET val=val+1';
        $this->typeInTextArea($proc);

        $action = $this->webDriver->action();
        // Resize the too big text box to access Go button
        $element = $this->byXPath('//*[@class="ui-resizable-handle ui-resizable-s"]');
        $action->moveToElement($element)
                ->clickAndHold()
                ->moveByOffset(0, -120)// Resize
                ->click()// Click to free the mouse
                ->perform();

        $this->byCssSelector('div.ui-dialog-buttonset button:nth-child(1)')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Event `test_event` has been created', $success->getText());
        $this->waitForElementNotPresent(
            'xpath',
            '//div[@id=\'alertLabel\' and not(contains(@style,\'display: none;\'))]',
        );

        // Refresh the page
        $this->webDriver->navigate()->refresh();

        self::assertTrue(
            $this->isElementPresent(
                'xpath',
                "//td[contains(., 'test_event')]",
            ),
        );

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW EVENTS WHERE Db=\'' . $this->databaseName . '\' AND Name=\'test_event\';',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame($this->databaseName, $this->getCellByTableClass('table_results', 1, 1));
                self::assertSame('test_event', $this->getCellByTableClass('table_results', 1, 2));
                self::assertSame('RECURRING', $this->getCellByTableClass('table_results', 1, 5));
            },
        );

        sleep(2);
        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.`test_table`',
            function (): void {
                $this->scrollToElement($this->waitForElement('className', 'table_results'), 0, 20);
                // [ ] | Edit | Copy | Delete | 1 | <number>
                self::assertGreaterThan(2, (int) $this->getCellByTableClass('table_results', 1, 6));
            },
        );
    }

    /**
     * Test for editing events
     */
    #[Depends('testAddEvent')]
    public function testEditEvents(): void
    {
        $this->eventSQL();
        $this->waitForElement('partialLinkText', 'Events')->click();
        $this->waitAjax();

        $this->waitForElement('xpath', '//div[contains(., "Event scheduler status")]');

        $this->byPartialLinkText('Edit')->click();

        $this->waitForElement('className', 'rte_form');
        $this->byName('item_interval_value')->clear();
        $this->byName('item_interval_value')->sendKeys('2');

        $this->byCssSelector('div.ui-dialog-buttonset button:nth-child(1)')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('Event `test_event` has been modified', $success->getText());

        sleep(2);
        $this->dbQuery(
            'SELECT * FROM `' . $this->databaseName . '`.`test_table`',
            function (): void {
                $this->scrollToElement($this->waitForElement('className', 'table_results'), 0, 20);
                // [ ] | Edit | Copy | Delete | 4
                self::assertGreaterThan(3, (int) $this->getCellByTableClass('table_results', 1, 6));
            },
        );
    }

    /**
     * Test for dropping event
     */
    #[Depends('testAddEvent')]
    public function testDropEvent(): void
    {
        $this->eventSQL();
        $this->waitForElement('partialLinkText', 'Events')->click();
        $this->waitAjax();

        $this->waitForElement('xpath', '//div[contains(., "Event scheduler status")]');

        $this->byPartialLinkText('Drop')->click();
        $this->waitForElement('id', 'functionConfirmOkButton')->click();

        $this->waitAjaxMessage();

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'SHOW EVENTS WHERE Db=\'' . $this->databaseName . '\' AND Name=\'test_event\';',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            },
        );
    }
}
