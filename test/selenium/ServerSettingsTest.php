<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use function sleep;

/**
 * @coversNothing
 */
class ServerSettingsTest extends TestBase
{
    /**
     * Create a test database for this test class
     *
     * @var bool
     */
    protected static $createDatabase = false;

    /**
     * setUp function
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->expandMore();
        $this->waitForElement('partialLinkText', 'Settings')->click();
        $this->waitAjax();
    }

    /**
     * Saves config and asserts correct message.
     */
    private function saveConfig(): void
    {
        // Submit the form
        $ele = $this->waitForElement(
            'xpath',
            "//div[contains(@class, 'tab-pane') and contains(@class, 'show')"
                . " and contains(@class, 'active')]//input[@value='Apply']"
        );
        $this->scrollToBottom();
        $this->moveto($ele);
        $ele->click();

        $this->waitUntilElementIsPresent(
            'xpath',
            "//div[@class='alert alert-success' and contains(., 'Configuration has been saved')]",
            5000
        );
    }

    /**
     * Tests whether hiding a database works or not
     *
     * @group large
     */
    public function testHideDatabase(): void
    {
        $this->createDatabase();
        $this->byPartialLinkText('Features')->click();
        $this->waitAjax();

        $this->waitForElement('xpath', "//a[contains(@href, '#Databases')]")->click();

        $ele = $this->waitForElement('name', 'Servers-1-hide_db');
        $this->moveto($ele);
        $ele->clear();
        $ele->sendKeys($this->databaseName);

        $this->saveConfig();
        self::assertFalse($this->isElementPresent('partialLinkText', $this->databaseName));

        $this->waitForElement('xpath', "//a[contains(@href, '#Databases')]")->click();

        $this->waitForElement('name', 'Servers-1-hide_db')->clear();
        $this->saveConfig();
        self::assertTrue($this->isElementPresent('partialLinkText', $this->databaseName));
    }

    /**
     * Tests whether the various settings tabs are displayed when clicked
     *
     * @group large
     */
    public function testSettingsTabsAreDisplayed(): void
    {
        $this->byPartialLinkText('SQL queries')->click();
        $this->waitAjax();

        $this->waitForElement('className', 'nav-tabs');

        $this->byPartialLinkText('SQL Query box')->click();
        self::assertTrue($this->byId('Sql_box')->isDisplayed());
        self::assertFalse($this->byId('Sql_queries')->isDisplayed());

        $this->byCssSelector("a[href='#Sql_queries']")->click();
        self::assertFalse($this->byId('Sql_box')->isDisplayed());
        self::assertTrue($this->byId('Sql_queries')->isDisplayed());
    }

    /**
     * Tests if hiding the logo works or not
     *
     * @group large
     */
    public function testHideLogo(): void
    {
        $this->byPartialLinkText('Navigation panel')->click();
        $this->waitAjax();

        $this->waitForElement('name', 'NavigationDisplayLogo')
            ->click();
        $this->saveConfig();
        sleep(1);
        self::assertFalse($this->isElementPresent('id', 'imgpmalogo'));

        $this->byCssSelector("a[href='#NavigationDisplayLogo']")->click();
        $this->saveConfig();
        sleep(1);
        self::assertTrue($this->isElementPresent('id', 'imgpmalogo'));
    }
}
