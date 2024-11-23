<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

#[CoversNothing]
#[Large]
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
     */
    public function testDbComment(): void
    {
        $this->skipIfNotPMADB();

        $this->getToDBOperations();
        $this->byName('comment')->sendKeys('comment_foobar');
        $this->byCssSelector("form#formDatabaseComment input[type='submit']")->click();

        $this->waitForElement('xpath', "//span[@class='breadcrumb-comment' and contains(., 'comment_foobar')]");
    }

    /**
     * Test for renaming database
     */
    public function testRenameDB(): void
    {
        $this->getToDBOperations();

        $newDbName = $this->databaseName . 'rename';

        $this->waitForElement('id', 'rename_db_form');
        $this->scrollIntoView('rename_db_form');
        $newNameInput = $this->byCssSelector('form#rename_db_form input[name=newname]');
        $newNameInput->clear();
        $newNameInput->sendKeys($newDbName);

        $this->byCssSelector("form#rename_db_form input[type='submit']")->click();

        $this->waitForElement('id', 'functionConfirmOkButton')->click();

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $newDbName . '\'',
            function () use ($newDbName): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame($newDbName, $this->getCellByTableClass('table_results', 1, 1));
            },
        );

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $this->databaseName . '\'',
            function (): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertFalse($this->isElementPresent('cssSelector', '.table_results tbody tr'));
            },
        );

        $this->databaseName = $newDbName;
    }

    /**
     * Test for copying database
     */
    public function testCopyDb(): void
    {
        $this->getToDBOperations();

        $this->reloadPage();// Reload or scrolling will not work ..
        $newDbName = $this->databaseName . 'copy';
        $this->scrollIntoView('renameDbNameInput');
        $newNameInput = $this->byCssSelector('form#copy_db_form input[name=newname]');
        $newNameInput->clear();
        $newNameInput->sendKeys($newDbName);

        $this->scrollIntoView('copy_db_form', -150);
        $this->byCssSelector('form#copy_db_form input[name="submit_copy"]')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString(
            'Database ' . $this->databaseName . ' has been copied to ' . $newDbName,
            $success->getText(),
        );

        $this->dbQuery(
            'SHOW DATABASES LIKE \'' . $newDbName . '\'',
            function () use ($newDbName): void {
                self::assertTrue($this->isElementPresent('className', 'table_results'));
                self::assertSame($newDbName, $this->getCellByTableClass('table_results', 1, 1));
            },
        );

        $this->dbQuery('DROP DATABASE `' . $newDbName . '`;');
    }
}
