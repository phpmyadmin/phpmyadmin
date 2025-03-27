<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * @coversNothing
 */
class XssTest extends TestBase
{
    /**
     * Create a test database for this test class
     *
     * @var bool
     */
    protected static $createDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    /**
     * Tests the SQL query tab with a null query
     *
     * @group large
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
