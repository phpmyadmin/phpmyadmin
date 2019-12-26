<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium\Database;

use PhpMyAdmin\Tests\Selenium\TestBase;

/**
 * OperationsTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class OperationsTest extends TestBase
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
    }

    /**
     * @return void
     */
    private function _getToDBOperations()
    {
        $this->gotoHomepage();

        $this->navigateDatabase($this->database_name);
        $this->expandMore();
        $this->maximize();
        $this->waitForElement('partialLinkText', 'Operations')->click();
        $this->waitForElement(
            'xpath',
            '//legend[contains(., \'Rename database to\')]'
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

        $this->_getToDBOperations();
        $this->byName("comment")->sendKeys("comment_foobar");
        $this->byCssSelector(
            "form#formDatabaseComment input[type='submit']"
        )->click();

        $this->assertNotNull(
            $this->waitForElement(
                'xpath',
                "//span[@id='span_table_comment' and contains(., 'comment_foobar')]"
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
        $this->_getToDBOperations();

        $new_db_name = $this->database_name . 'rename';

        $this->scrollIntoView('create_table_form_minimal');
        $this->byCssSelector("form#rename_db_form input[name=newname]")
            ->sendKeys($new_db_name);

        $this->byCssSelector("form#rename_db_form input[type='submit']")->click();

        $this->waitForElement(
            'cssSelector',
            "button.submitOK"
        )->click();

        $this->waitForElement(
            'xpath',
            "//a[@class='item' and contains(., 'Database: $new_db_name')]"
        );

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE '$new_db_name';"
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
        $this->_getToDBOperations();

        $new_db_name = $this->database_name . 'copy';
        $this->byCssSelector("form#copy_db_form input[name=newname]")
            ->sendKeys($new_db_name);

        $this->scrollIntoView('copy_db_form', -150);
        $this->byCssSelector("form#copy_db_form input[type='submit']")->click();

        $this->waitForElement(
            'xpath',
            "//div[@class='success' and contains(., 'Database "
            . $this->database_name
            . " has been copied to $new_db_name')]"
        );

        $result = $this->dbQuery(
            "SHOW DATABASES LIKE '$new_db_name';"
        );
        $this->assertEquals(1, $result->num_rows);

        $this->dbQuery("DROP DATABASE $new_db_name");
    }
}
