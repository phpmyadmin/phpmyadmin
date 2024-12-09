<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Table;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
class InsertTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium t
     * est case
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbQuery(
            'USE `' . $this->databaseName . '`;'
            . 'CREATE TABLE `test_table` ('
            . ' `id` int(11) NOT NULL AUTO_INCREMENT,'
            . ' `name` varchar(20) NOT NULL,'
            . ' `datetimefield` datetime NOT NULL,'
            . ' PRIMARY KEY (`id`)'
            . ');',
        );

        $this->login();
        $this->navigateTable('test_table');
    }

    /**
     * Insert data into table
     */
    public function testAddData(): void
    {
        if ($this->isSafari()) {
            /* TODO: this should be fixed, but the cause is unclear to me */
            self::markTestIncomplete('Fails with Safari');
        }

        $this->waitAjax();
        $this->expandMore();

        $this->byPartialLinkText('Insert')->click();
        $this->waitAjax();
        $this->waitForElement('id', 'insertForm');

        // shorter date to prevent error,
        // automatically gets appended with 00:00:00
        $this->byId('field_3_3')->click()->clear()->sendKeys('2011-01-2');

        $this->byId('field_1_3')->sendKeys('1');
        $this->byId('field_2_3')->sendKeys('abcd');

        // shorter date to prevent error,
        // automatically gets appended with 00:00:00
        $this->byId('field_6_3')->click()->clear()->sendKeys('2012-01-2');

        $this->byId('field_5_3')->sendKeys('foo');

        $this->selectByLabel(
            $this->byName('after_insert'),
            'Insert another new row',
        );

        // post
        $this->byId('buttonYes')->click();
        $this->waitAjax();

        $ele = $this->waitForElement('className', 'alert-success');
        self::assertStringContainsString('2 rows inserted', $ele->getText());

        // shorter date to prevent error,
        // automatically gets appended with 00:00:00
        $this->byId('field_3_3')->click()->clear()->sendKeys('2013-01-2');

        $this->byId('field_2_3')->sendKeys('Abcd');

        // post
        $this->byCssSelector('input[value=Go]')->click();

        $this->waitAjax();

        // New message
        $ele = $this->waitForElement(
            'xpath',
            "//div[contains(@class, 'alert-success') and not(contains(@class, 'message'))]",
        );
        self::assertStringContainsString('1 row inserted', $ele->getText());

        $this->assertDataPresent();
    }

    /**
     * Assert various data present in results table
     */
    private function assertDataPresent(): void
    {
        $this->byPartialLinkText('Browse')->click();

        $this->waitAjax();
        $this->waitForElement('cssSelector', 'table.table_results');

        self::assertSame(
            '1',
            $this->getCellByTableClass('table_results', 1, 5),
        );

        self::assertSame(
            'abcd',
            $this->getCellByTableClass('table_results', 1, 6),
        );

        self::assertSame(
            '2011-01-02 00:00:00',
            $this->getCellByTableClass('table_results', 1, 7),
        );

        self::assertSame(
            '2',
            $this->getCellByTableClass('table_results', 2, 5),
        );

        self::assertSame(
            'foo',
            $this->getCellByTableClass('table_results', 2, 6),
        );

        self::assertSame(
            '2012-01-02 00:00:00',
            $this->getCellByTableClass('table_results', 2, 7),
        );

        self::assertSame(
            '4',
            $this->getCellByTableClass('table_results', 3, 5),
        );

        self::assertSame(
            'Abcd',
            $this->getCellByTableClass('table_results', 3, 6),
        );

        self::assertSame(
            '2013-01-02 00:00:00',
            $this->getCellByTableClass('table_results', 3, 7),
        );
    }
}
