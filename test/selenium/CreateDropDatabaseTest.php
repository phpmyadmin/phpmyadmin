<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for creating and deleting databases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * CreateDropDatabaseTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class CreateDropDatabaseTest extends TestBase
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

    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
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
        // Drop database if it exists
        $this->dbQuery(
            'DROP DATABASE IF EXISTS ' . $this->database_name . ';'
        );

        $this->waitForElement('byPartialLinkText','Databases')->click();
        $this->waitAjax();

        $element = $this->waitForElement('byId', 'text_create_db');
        $element->clear();
        $element->value($this->database_name);

        $this->byId("buttonGo")->click();

        $element = $this->waitForElement('byLinkText', 'Database: ' . $this->database_name);

        $result = $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->database_name . '\';'
        );
        $this->assertEquals(1, $result->num_rows);

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

        $this->byPartialLinkText('Databases')->click();
        $this->waitAjax();

        $this->scrollToBottom();
        $this->byCssSelector(
            "input[name='selected_dbs[]'][value='" . $this->database_name . "']"
        )->click();

        $this->byCssSelector("button.mult_submit")->click();
        $this->byCssSelector("button.submitOK")->click();

        $this->waitForElementNotPresent(
            "byCssSelector",
            "input[name='selected_dbs[]'][value='" . $this->database_name . "']"
        );

        $this->waitForElement(
            "byCssSelector", "span.ajax_notification div.success"
        );

        $result = $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->database_name . '\';'
        );
        $this->assertEquals(0, $result->num_rows);
    }
}
