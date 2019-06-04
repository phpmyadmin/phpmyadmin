<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for settings related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * ServerSettingsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class ServerSettingsTest extends TestBase
{
    /**
     * setUp function
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->expandMore();
        $this->maximize();
        $this->waitForElement('partialLinkText', "Settings")->click();
        $this->waitAjax();

        $this->waitForElement(
            'xpath',
            "//a[@class='tabactive' and contains(., 'Settings')]"
        );
    }

    /**
     * Saves config and asserts correct message.
     *
     * @return void
     */
    private function _saveConfig()
    {
        // Submit the form
        $ele = $this->waitForElement(
            'xpath',
            "//fieldset[not(contains(@style,'display: none;'))]//input[@value='Apply']"
        );
        $this->scrollToBottom();
        $this->moveto($ele);
        $ele->click();

        $this->waitUntilElementIsPresent(
            'xpath',
            "//div[@class='success' and contains(., 'Configuration has been saved')]",
            5000
        );
    }

    /**
     * Tests whether hiding a database works or not
     *
     * @return void
     *
     * @group large
     */
    public function testHideDatabase()
    {
        $this->byPartialLinkText("Features")->click();
        $this->waitAjax();

        $this->waitForElement('xpath', "//a[contains(@href, '#Databases')]")->click();

        $ele = $this->waitForElement('name', "Servers-1-hide_db");
        $this->moveto($ele);
        $ele->sendKeys($this->database_name);

        $this->_saveConfig();
        $this->assertFalse(
            $this->isElementPresent('partialLinkText', $this->database_name)
        );

        $this->waitForElement('name', "Servers-1-hide_db")->clear();
        $this->_saveConfig();
        $this->assertTrue(
            $this->isElementPresent('partialLinkText', $this->database_name)
        );
    }

    /**
     * Tests whether the various settings tabs are displayed when clicked
     *
     * @return void
     *
     * @group large
     */
    public function testSettingsTabsAreDisplayed()
    {
        $this->byPartialLinkText("SQL queries")->click();
        $this->waitAjax();

        $this->waitForElement('className', 'tabs');

        $this->byPartialLinkText("SQL Query box")->click();
        $this->assertTrue(
            $this->byId("Sql_box")->isDisplayed()
        );
        $this->assertFalse(
            $this->byId("Sql_queries")->isDisplayed()
        );

        $this->byCssSelector("a[href='#Sql_queries']")->click();
        $this->assertFalse(
            $this->byId("Sql_box")->isDisplayed()
        );
        $this->assertTrue(
            $this->byId("Sql_queries")->isDisplayed()
        );
    }

    /**
     * Tests if hiding the logo works or not
     *
     * @return void
     *
     * @group large
     */
    public function testHideLogo()
    {
        $this->byPartialLinkText("Navigation panel")->click();
        $this->waitAjax();

        $this->waitForElement('name', "NavigationDisplayLogo")
            ->click();
        $this->_saveConfig();
        $this->assertFalse(
            $this->isElementPresent('id', "imgpmalogo")
        );

        $this->byCssSelector("a[href='#NavigationDisplayLogo']")->click();
        $this->_saveConfig();
        $this->assertTrue(
            $this->isElementPresent('id', "imgpmalogo")
        );
    }
}
