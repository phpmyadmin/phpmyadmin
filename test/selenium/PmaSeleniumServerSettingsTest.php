<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for settings related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumServerSettingsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumSettingsTest extends PMA_SeleniumBase
{
    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        $this->login();
        $this->expandMore();
        $this->waitForElement("byLinkText", "Settings")->click();
        $this->waitForElement(
            "byXPath", "//a[@class='tabactive' and contains(., 'Settings')]"
        );
        $this->sleep();
    }

    /**
     * Saves config and asserts correct message.
     *
     * @return void
     */
    private function _saveConfig()
    {
        $this->byName("submit_save")->click();
        $this->sleep();
        $this->waitForElement(
            "byXPath",
            "//div[@class='success' and contains(., 'Configuration has been saved')]"
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
        /* FIXME: This test fails even though it is same as testHideLogo */
        $this->markTestIncomplete('Currently broken');

        $this->byLinkText("Features")->click();

        $this->waitForElement("byName", "Servers-1-hide_db")
            ->value($this->database_name);
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
        $this->byLinkText("SQL queries")->click();
        $this->waitForElement('byClassName', 'tabs');

        $this->byLinkText("SQL Query box")->click();
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
        $this->byLinkText("Navigation panel")->click();

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
