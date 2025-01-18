<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
class NormalizationTest extends TestBase
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
            . ' `val2` varchar(64) NOT NULL,'
            . 'PRIMARY KEY(id)'
            . ');',
        );

        $this->login();
        $this->navigateTable('test_table');
        $this->waitForElement('xpath', "(//a[contains(., 'Structure')])")->click();

        $this->waitAjax();

        $this->waitForElement('id', 'tablestructure');
        $this->byPartialLinkText('Normalize')->click();
        $this->waitForElement('id', 'normalizeTable');
    }

    /**
     * Test for normalization to 1NF
     */
    public function testNormalizationTo1NF(): void
    {
        self::assertSame(
            'First step of normalization (1NF)',
            $this->byCssSelector('label[for=normalizeToRadio1]')->getText(),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                'input[id=normalizeToRadio1][type=radio]:checked',
            ),
        );
        $this->byCssSelector('input[name=submit_normalize]')->click();
        $this->waitForElement('id', 'mainContent');
        $this->assert1NFSteps();
    }

    /**
     * assertions in 1NF steps 1.1, 1.2, 1.3
     */
    private function assert1NFSteps(): void
    {
        self::assertSame(
            'First step of normalization (1NF)',
            $this->byCssSelector('#page_content h3')->getText(),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#mainContent h4',
            ),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#mainContent #newCols',
            ),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#selectNonAtomicCol option[value=val2]',
            ),
        );
        self::assertFalse(
            $this->isElementPresent(
                'cssSelector',
                '#selectNonAtomicCol option[value=val]',
            ),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#selectNonAtomicCol option[value=no_such_col]',
            ),
        );

        $this->selectByValue(
            $this->byId('selectNonAtomicCol'),
            'no_such_col',
        );

        $this->waitForElement('xpath', "//div[contains(., 'Step 1.2 Have a primary key')]");
        $text = $this->byCssSelector('#mainContent h4')->getText();
        self::assertStringContainsString('Primary key already exists.', $text);
        $this->waitForElement('xpath', "//div[contains(., 'Step 1.3 Move repeating groups')]");
        $this->byCssSelector('input[value="No repeating group"]')->click();
        $this->waitForElement('xpath', "//div[contains(., 'Step 1.4 Remove redundant columns')]");
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#mainContent #extra',
            ),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#extra input[value=val2][type=checkbox]',
            ),
        );
        self::assertTrue(
            $this->isElementPresent(
                'cssSelector',
                '#extra input[value=id][type=checkbox]',
            ),
        );
        $this->byCssSelector('#extra input[value=val][type=checkbox]')->click();
        $this->byCssSelector('#removeRedundant')->click();
        $this->waitForElement('xpath', "//div[contains(., 'End of step')]");
        self::assertStringContainsString(
            "The first step of normalization is complete for table 'test_table'.",
            $this->byCssSelector('#mainContent h4')->getText(),
        );
    }
}
