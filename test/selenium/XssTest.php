<?php
/**
 * Selenium TestCase for SQL query window related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * XssTest class
 *
 * @group      selenium
 */
class XssTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    /**
     * Tests the SQL query tab with a null query
     *
     * @return void
     *
     * @group large
     */
    public function testQueryTabWithNullValue()
    {
        if ($this->isSafari()) {
            $this->markTestSkipped('Alerts not supported on Safari browser.');
        }
        $this->waitForElement('partialLinkText', 'SQL')->click();
        $this->waitAjax();

        $this->waitForElement('id', 'querybox');
        $this->byId('button_submit_query')->click();
        $this->assertEquals('Missing value in the form!', $this->alertText());
    }
}
