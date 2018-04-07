<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for settings related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * ServerSettingsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class SettingsTest extends TestBase
{
    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        parent::setUpPage();

        $this->login();
        $this->expandMore();
        $this->waitForElement("byPartialLinkText", "Settings")->click();
        $this->waitAjax();

        $this->waitForElement(
            "byXPath", "//a[@class='tabactive' and contains(., 'Settings')]"
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
            'byXPath',
            "//fieldset[not(contains(@style,'display: none;'))]//input[@value='Apply']"
        );
        $this->scrollToBottom();
        $this->moveto($ele);
        $ele->click();

        $this->waitUntil(function() {
            if (
                $this->isElementPresent(
                    "byXPath",
                    "//div[@class='success' and contains(., 'Configuration has been saved')]"
                )
            ) {
                return true;
            }

            return null;
        }, 5000);
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

        $this->waitForElement('byXPath', "//a[contains(@href, '#Databases')]")->click();

        $ele = $this->waitForElement("byName", "Servers-1-hide_db");
        $this->moveto($ele);
        $ele->value($this->database_name);

        $this->_saveConfig();
        $this->assertFalse(
            $this->isElementPresent("byLinkText", $this->database_name)
        );

        $this->waitForElement("byName", "Servers-1-hide_db")->clear();
        $this->_saveConfig();
        $this->assertTrue(
            $this->isElementPresent("byLinkText", $this->database_name)
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

        $this->waitForElement('byClassName', 'tabs');

        $this->byPartialLinkText("SQL Query box")->click();
        $this->assertTrue(
            $this->byId("Sql_box")->displayed()
        );
        $this->assertFalse(
            $this->byId("Sql_queries")->displayed()
        );

        $this->byCssSelector("a[href='#Sql_queries']")->click();
        $this->assertFalse(
            $this->byId("Sql_box")->displayed()
        );
        $this->assertTrue(
            $this->byId("Sql_queries")->displayed()
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

        $this->waitForElement("byName", "NavigationDisplayLogo")
            ->click();
        $this->_saveConfig();
        $this->assertFalse(
            $this->isElementPresent("byId", "imgpmalogo")
        );

        $this->byCssSelector("a[href='#NavigationDisplayLogo']")->click();
        $this->_saveConfig();
        $this->assertTrue(
            $this->isElementPresent("byId", "imgpmalogo")
        );
    }

}
