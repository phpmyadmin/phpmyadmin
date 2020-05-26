<?php
/**
 * Selenium TestCase for creating and deleting databases
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * CreateDropDatabaseTest class
 *
 * @group      selenium
 */
class CreateDropDatabaseTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        /* TODO: For now this tests needs superuser for deleting database */
        $this->skipIfNotSuperUser();
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
        $this->dbQuery(
            'DROP DATABASE IF EXISTS `' . $this->database_name . '`;'
        );

        $this->waitForElement('partialLinkText', 'Databases')->click();
        $this->waitAjax();

        $element = $this->waitForElement('id', 'text_create_db');
        $element->clear();
        $element->sendKeys($this->database_name);

        $this->byId('buttonGo')->click();

        $this->waitForElement('linkText', 'Database: ' . $this->database_name);

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

        $dbElement = $this->byCssSelector(
            "input[name='selected_dbs[]'][value='" . $this->database_name . "']"
        );
        $this->scrollToElement($dbElement, 0, 20);
        $dbElement->click();

        $multSubmit = $this->byCssSelector('button.mult_submit');
        $this->scrollToElement($multSubmit);
        $multSubmit->click();
        $this->byCssSelector('button.submitOK')->click();

        $this->waitForElementNotPresent(
            'cssSelector',
            "input[name='selected_dbs[]'][value='" . $this->database_name . "']"
        );

        $this->waitForElement(
            'cssSelector',
            'span.ajax_notification .alert-success'
        );

        $result = $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->database_name . '\';'
        );
        $this->assertEquals(0, $result->num_rows);
    }
}
