<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;
use function date;
use function sleep;
use function strtotime;

/**
 * EventsTest class
 *
 * @group      selenium
 */
class EventsTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dbQuery(
            'USE `' . $this->database_name . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `val` int(11) NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');'
            . 'INSERT INTO `test_table` (val) VALUES (2);'
            . 'SET GLOBAL event_scheduler="ON";'
        );
        $this->login();
        $this->navigateDatabase($this->database_name);

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
     *
     * @return void
     */
    private function eventSQL()
    {
        $start = date('Y-m-d H:i:s', strtotime('-1 day'));
        $end = date('Y-m-d H:i:s', strtotime('+1 day'));

        $this->dbQuery(
            'CREATE EVENT `test_event` ON SCHEDULE EVERY 1 MINUTE_SECOND STARTS '
            . "'" . $start . "' ENDS '" . $end . "' ON COMPLETION NOT PRESERVE ENABLE "
            . 'DO UPDATE `' . $this->database_name
            . '`.`test_table` SET val = val + 1'
        );
    }

    /**
     * Create an event
     *
     * @return void
     *
     * @group large
     */
    public function testAddEvent()
    {
        $this->waitForElement('partialLinkText', 'Events')->click();
        $this->waitAjax();

        $this->waitForElement('partialLinkText', 'Add event')->click();
        $this->waitAjax();

        $this->waitForElement('className', 'rte_form');

        $this->selectByLabel($this->byName('item_type'), 'RECURRING');

        $this->byName('item_name')->sendKeys('test_event');
        $this->selectByLabel(
            $this->byName('item_interval_field'),
            'MINUTE_SECOND'
        );

        $this->byName('item_starts')->click()->clear()->sendKeys(date('Y-m-d', strtotime('-1 day')) . ' 00:00:00');

        $this->byName('item_ends')->click()->clear()->sendKeys(date('Y-m-d', strtotime('+1 day')) . ' 00:00:00');

        $this->waitForElement('name', 'item_interval_value')->click()->clear()->sendKeys('1');

        $proc = 'UPDATE ' . $this->database_name . '.`test_table` SET val=val+1';
        $this->typeInTextArea($proc);

        $action = $this->webDriver->action();
        // Resize the too big text box to access Go button
        $element = $this->byXPath('//*[@class="ui-resizable-handle ui-resizable-s"]');
        $action->moveToElement($element)
                ->clickAndHold()
                ->moveByOffset(0, -100)
                ->perform();

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Event `test_event` has been created')]"
        );
        $this->waitForElementNotPresent(
            'xpath',
            '//div[@id=\'alertLabel\' and not(contains(@style,\'display: none;\'))]'
        );

        // Refresh the page
        $this->webDriver->navigate()->refresh();

        $this->assertTrue(
            $this->isElementPresent(
                'xpath',
                "//td[contains(., 'test_event')]"
            )
        );

        $this->dbQuery(
            "SHOW EVENTS WHERE Db='" . $this->database_name
            . "' AND Name='test_event'",
            function () {
                //TODO: improve the condition
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
            }
        );

        sleep(2);
        $this->dbQuery(
            'SELECT val FROM `' . $this->database_name . '`.`test_table`',
            function () {
                //TODO: improve the condition
                $this->assertTrue($this->isElementPresent('className', 'table_results'));
                $this->assertEquals($this->database_name, $this->getCellByTableClass('table_results', 1, 1));
                //$this->assertGreaterThan(2, $row['val']);
            }
        );
    }

    /**
     * Test for editing events
     *
     * @return void
     *
     * @depends testAddEvent
     * @group large
     */
    public function testEditEvents()
    {
        $this->eventSQL();
        $this->waitForElement('partialLinkText', 'Events')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Events')]"
        );

        $this->byPartialLinkText('Edit')->click();

        $this->waitForElement('className', 'rte_form');
        $this->byName('item_interval_value')->clear();
        $this->byName('item_interval_value')->sendKeys('2');

        $this->byXPath("//button[contains(., 'Go')]")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., "
            . "'Event `test_event` has been modified')]"
        );

        sleep(2);
        $result = $this->dbQuery(
            'SELECT val FROM `' . $this->database_name . '`.`test_table`'
        );
        $row = $result->fetch_assoc();
        $this->assertGreaterThan(2, $row['val']);
    }

    /**
     * Test for dropping event
     *
     * @return void
     *
     * @depends testAddEvent
     * @group large
     */
    public function testDropEvent()
    {
        $this->eventSQL();
        $this->waitForElement('partialLinkText', 'Events')->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//legend[contains(., 'Events')]"
        );

        $this->byPartialLinkText('Drop')->click();
        $this->waitForElement(
            'className',
            'submitOK'
        )->click();

        $this->waitAjaxMessage();

        $result = $this->dbQuery(
            "SHOW EVENTS WHERE Db='" . $this->database_name
            . "' AND Name='test_event'"
        );
        $this->assertEquals(0, $result->num_rows);
    }
}
