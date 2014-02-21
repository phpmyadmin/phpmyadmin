<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for SQL querry window related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'TestBase.php';

/**
 * PmaSeleniumXSSTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PMA_SeleniumXSSTest extends PMA_SeleniumBase
{
    /**
     * Tests the SQL query tab with a null query
     *
     * @return void
     *
     * @group large
     */
    public function testQueryTabWithNullValue()
    {
        $this->login();
        $this->byLinkText("SQL")->click();
        $this->waitForElement("byId", "queryboxf");
        $this->byId("button_submit_query")->click();
        $this->assertEquals("Missing value in the form!", $this->alertText());
    }
}
?>
