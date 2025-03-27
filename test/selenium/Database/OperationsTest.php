<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * @coversNothing
 */
class OperationsTest extends TestBase
{
    /**
     * setUp function
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    private function getToDBOperations(): void
    {
        $this->gotoHomepage();

        $this->navigateDatabase($this->databaseName);
        $this->expandMore();
        $this->waitForElement('partialLinkText', 'Operations')->click();
        $this->waitForElement('xpath', '//div[contains(., \'Rename database to\')]');
    }

    /**
     * Test for adding database comment
     *
     * @group large
     */
    public function testDbComment(): void
    {
        $this->skipIfNotPMADB();

        $this->getToDBOperations();
        $this->byName('comment')->sendKeys('comment_foobar');
        $this->byCssSelector("form#formDatabaseComment input[type='submit']")->click();

        self::assertNotNull($this->waitForElement(
            'xpath',
            "//span[@class='breadcrumb-comment' and contains(., 'comment_foobar')]"
        ));
    }

    /**
     * Test for renaming database
     *
     * @group large
     */
    public function testRenameDB(): void
    {
        $this->getToDBOperations();

        $new_db_name = $this->databaseName . 'rename';

        $this->scrollIntoView('createTableMinimalForm');
        $newNameInput = $this->byCssSelector('form#rename_db_form input[name=newname]');
        $newNameInput->clear();
        $newNameInput->sendKeys($new_db_name);

        $this->byCssSelector("form#rename_db_form input[type='submit']")->click();

        $this->waitForElement('cssSelector', 'button.submitOK')->click();

        $this->waitForElement(
            'xpath',
            "//a[contains(text(),'Database: ') and contains(text(),'" . $new_db_name . "')]"
        );

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $new_db_name . '\'',
            function () use ($new_db_name): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertEquals($new_db_name, $this->getCellByTableClass('table_results', 1, 1));
            }
        );

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->databaseName . '\'',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            }
        );

        $this->databaseName = $new_db_name;
    }

    /**
     * Test for copying database
     *
     * @group large
     */
    public function testCopyDb(): void
    {
        $this->getToDBOperations();

        $this->reloadPage();// Reload or scrolling will not work ..
        $new_db_name = $this->databaseName . 'copy';
        $this->scrollIntoView('renameDbNameInput');
        $newNameInput = $this->byCssSelector('form#copy_db_form input[name=newname]');
        $newNameInput->clear();
        $newNameInput->sendKeys($new_db_name);

        $this->scrollIntoView('copy_db_form', -150);
        $this->byCssSelector('form#copy_db_form input[name="submit_copy"]')->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., 'Database "
            . $this->databaseName
            . ' has been copied to ' . $new_db_name . "')]"
        );

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $new_db_name . '\'',
            function () use ($new_db_name): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertEquals($new_db_name, $this->getCellByTableClass('table_results', 1, 1));
            }
        );

        $this->dbQuery('DROP DATABASE `' . $new_db_name . '`;');
    }
}
