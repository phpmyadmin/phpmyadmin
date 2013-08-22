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
class PmaSeleniumXSSTest extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * Helper Object
     *
     * @var obj
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
        $this->click("link=SQL");
        $this->waitForElementPresent("id=queryboxf");
        $this->click("button_submit_query");
        $this->assertAlert("Missing value in the form!");
    }
}
?>
