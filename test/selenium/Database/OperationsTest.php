<?php
/**
 * Selenium TestCase for table related tests
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * OperationsTest class
 *
 * @group      selenium
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

    /**
     * @return void
     */
    private function getToDBOperations()
    {
        $this->gotoHomepage();

        $this->navigateDatabase($this->database_name);
        $this->expandMore();
        $this->waitForElement('partialLinkText', 'Operations')->click();
        $this->waitForElement(
            'xpath',
            '//div[contains(., \'Rename database to\')]'
        );
    }

    /**
     * Test for adding database comment
     *
     * @return void
     *
     * @group large
     */
    public function testDbComment()
    {
        $this->skipIfNotPMADB();

        $this->getToDBOperations();
        $this->byName('comment')->sendKeys('comment_foobar');
        $this->byCssSelector(
            "form#formDatabaseComment input[type='submit']"
        )->click();

        $this->assertNotNull(
            $this->waitForElement(
                'xpath',
                "//span[@class='breadcrumb-comment' and contains(., 'comment_foobar')]"
            )
        );
    }

    /**
     * Test for renaming database
     *
     * @return void
     *
     * @group large
     */
    public function testRenameDB()
    {
        $this->getToDBOperations();

        $new_db_name = $this->database_name . 'rename';

        $this->scrollIntoView('create_table_form_minimal');
        $this->byCssSelector('form#rename_db_form input[name=newname]')
            ->sendKeys($new_db_name);

        $this->byCssSelector("form#rename_db_form input[type='submit']")->click();

        $this->waitForElement(
            'cssSelector',
            'button.submitOK'
        )->click();

        $this->waitForElement(
            'xpath',
            "//a[contains(text(),'Database: ') and contains(text(),'" . $new_db_name . "')]"
        );

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE '" . $new_db_name . "';"
        );
        $this->assertEquals(1, $result->num_rows);

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE '" . $this->database_name . "';"
        );
        $this->assertEquals(0, $result->num_rows);

        $this->database_name = $new_db_name;
    }

    /**
     * Test for copying database
     *
     * @return void
     *
     * @group large
     */
    public function testCopyDb()
    {
        $this->getToDBOperations();

        $this->reloadPage();// Reload or scrolling will not work ..
        $new_db_name = $this->database_name . 'copy';
        $this->byCssSelector('form#copy_db_form input[name=newname]')
            ->sendKeys($new_db_name);

        $this->scrollIntoView('copy_db_form', -150);
        $this->byCssSelector('form#copy_db_form input[name="submit_copy"]')->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='alert alert-success' and contains(., 'Database "
            . $this->database_name
            . ' has been copied to ' . $new_db_name . "')]"
        );

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE '" . $new_db_name . "';"
        );
        $this->assertEquals(1, $result->num_rows);

        $this->dbQuery('DROP DATABASE `' . $new_db_name . '`;');
    }
}
