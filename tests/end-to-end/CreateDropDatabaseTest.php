<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
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
     */
    public function testCreateDropDatabase(): void
    {
        $this->dbQuery('DROP DATABASE IF EXISTS `' . $this->databaseName . '`;');

        $this->waitForElement('partialLinkText', 'Databases')->click();
        $this->waitAjax();

        $element = $this->waitForElement('id', 'text_create_db');
        $element->clear();
        $element->sendKeys($this->databaseName);

        $this->byId('buttonGo')->click();

        $this->waitForElement('linkText', 'Database: ' . $this->databaseName);

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->databaseName . '\';',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame($this->databaseName, $this->getCellByTableClass('table_results', 1, 1));
            },
        );

        $this->dropDatabase();
    }

    /**
     * Drops a database, called after testCreateDropDatabase
     */
    private function dropDatabase(): void
    {
        $this->gotoHomepage();

        $this->byPartialLinkText('Databases')->click();
        $this->waitAjax();

        $this->scrollToBottom();

        $dbElement = $this->byCssSelector("input[name='selected_dbs[]'][value='" . $this->databaseName . "']");
        $this->scrollToElement($dbElement, 0, 20);
        $dbElement->click();

        $multSubmit = $this->byCssSelector('#bulkActionDropButton');
        $this->scrollToElement($multSubmit);
        $multSubmit->click();
        $this->byCssSelector('#dropDatabaseModalDropButton')->click();

        $this->waitForElementNotPresent(
            'cssSelector',
            "input[name='selected_dbs[]'][value='" . $this->databaseName . "']",
        );

        $this->waitForElement('cssSelector', 'span.ajax_notification .alert-success');

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->databaseName . '\';',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            },
        );
    }
}
