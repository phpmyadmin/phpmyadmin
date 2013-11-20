<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for SQL querry window related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

require_once 'Helper.php';

/**
 * PmaSeleniumXSSTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumXSSTest extends PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * Helper Object
     *
     * @var Helper
     */
    private $_helper;

    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $this->_helper = new Helper($this);
        $this->setBrowser($this->_helper->getBrowserString());
        $this->setBrowserUrl(
            TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL
        );
    }

    /**
     * Tests the SQL query tab with a null query
     *
     * @return void
     */
    public function testQueryTabWithNullValue()
    {
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText("SQL")->click();
        $this->_helper->waitForElement("byId", "queryboxf");
        $this->byId("button_submit_query")->click();
        $this->assertEquals("Missing value in the form!", $this->alertText());
    }
}
?>
