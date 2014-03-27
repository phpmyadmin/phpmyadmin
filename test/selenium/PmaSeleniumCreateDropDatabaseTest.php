<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for creating and deleting databases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumCreateDropDatabaseTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class PMA_SeleniumCreateDropDatabaseTest extends PMA_SeleniumBase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        /* TODO: For now this tests needs superuser for deleting database */
        $this->skipIfNotSuperUser();
    }

    /**
     * Creates a database and drops it
     *
     * @return void
     *
     * @group large
     */
    public function testCreateDropDatabase()
    {
        $this->login();

        $this->_dropDatabase();

        $this->waitForElement('byLinkText', "Databases")->click();
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');

        $element = $this->waitForElement('byId', 'text_create_db');
        $element->clear();
        $element->value($this->database_name);

        $this->byId("buttonGo")->click();

        $element = $this->waitForElement(
            "byCssSelector", "span.ajax_notification div.success"
        );

        $this->_dropDatabase();
    }

    /**
     * Drops a database, called after testCreateDropDatabase
     *
     * @return void
     */
    private function _dropDatabase()
    {
        $this->gotoHomepage();

        $this->byLinkText("Databases")->click();
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');

        $this->byCssSelector(
            "input[name='selected_dbs[]'][value='" . $this->database_name . "']"
        )->click();

        $this->byCssSelector("button.mult_submit")->click();
        $this->byCssSelector("span.ui-button-text:nth-child(1)")->click();

        $this->waitForElementNotPresent(
            "byCssSelector",
            "input[name='selected_dbs[]'][value='" . $this->database_name . "']"
        );

        $this->waitForElement(
            "byCssSelector", "span.ajax_notification div.success"
        );
    }
}
