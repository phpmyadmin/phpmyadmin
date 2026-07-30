<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

/**
 * @coversNothing
 * @large
 */
#[CoversNothing]
#[Large]
class XssTest extends TestBase
{
    /**
     * Create a test database for this test class
     *
     * @var bool
     */
    protected $createDatabase = false;

    /**
     * Tests the SQL query tab with a null query
     */
    public function testQueryTabWithNullValue(): void
    {
        if ($this->isSafari()) {
            $this->markTestSkipped('Alerts not supported on Safari browser.');
        }

        $this->waitForElement('partialLinkText', 'SQL')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'querybox');
        $this->byId('button_submit_query')->click();
        self::assertEquals('Missing value in the form!', $this->alertText());
    }
}
