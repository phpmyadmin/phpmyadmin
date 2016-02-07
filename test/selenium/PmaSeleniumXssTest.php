<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for SQL query window related tests
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
 * @group      selenium
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
        if (mb_strtolower($this->getBrowser()) == 'safari') {
            $this->markTestSkipped('Alerts not supported on Safari browser.');
        }
        $this->login();
        $this->waitForElement('byLinkText', "SQL")->click();
        $this->waitForElement("byId", "queryboxf");
        $this->byId("button_submit_query")->click();
        $this->assertEquals("Missing value in the form!", $this->alertText());
    }
}
