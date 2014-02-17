<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for Selenium tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

define('BS_UNAME', getenv('BS_UNAME'));
define('BS_KEY', getenv('BS_KEY'));
define('SELENIUM_URL', TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);


/**
 * Base class for Selenium tests.
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
abstract class PMA_SeleniumBase extends PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * Selenium browsers setup
     *
     * @access public
     * @var browsers
     */
	public static $browsers = array(
            array(
                'browserName' => 'firefox',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'desiredCapabilities' => array(
                    'browserstack.user' => BS_UNAME,
                    'browserstack.key' => BS_KEY,
                )
            ),
            array(
                'browserName' => 'chrome',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'desiredCapabilities' => array(
                    'browserstack.user' => BS_UNAME,
                    'browserstack.key' => BS_KEY,
                )
            ),
            array(
                'browserName' => 'internet_explorer',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'desiredCapabilities' => array(
                    'browserstack.user' => BS_UNAME,
                    'browserstack.key' => BS_KEY,
                )
            )
        );

    /**
     * mysqli object
     *
     * @access private
     * @var mysqli
     */
    private $_mysqli;


    /**
     * perform a login
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return void
     */
    public function login($username, $password)
    {
        $this->url('');
        $usernameField = $this->byId('input_username');
        $usernameField->value($username);
        $passwordField = $this->byId('input_password');
        $passwordField->value($password);
        $this->byId('input_go')->click();
    }

    /**
     * Checks whether the login is successful
     *
     * @return boolean
     */
    public function isSuccessLogin()
    {
        if ($this->isElementPresent("byXPath", "//*[@id=\"serverinfo\"]")) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Checks whether the login is unsuccessful
    *
    * @return boolean
    */
    public function isUnsuccessLogin()
    {
        if ($this->isElementPresent("byCssSelector", "div.error")) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Used to go to the homepage
    *
    * @return void
    */
    public function gotoHomepage()
    {
        $e = $this->byPartialLinkText("Server: ");
        $e->click();
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');
    }

    /**
     * Establishes a connection with the local database
     *
     * @return void
     *
     * @throws Exception
     */
    public function dbConnect()
    {
        if ($this->_mysqli === null) {
            $this->_mysqli = new mysqli(
                "localhost",
                TESTSUITE_USER,
                TESTSUITE_PASSWORD
            );
            if ($this->_mysqli->connect_errno) {
                throw new Exception(
                    'Failed to connect to MySQL (' . $this->_mysqli->error . ')'
                );
            }
        }
    }

    /**
     * Executes a database query
     *
     * @param string $query SQL Query to be executed
     *
     * @return void|boolean|mysqli_result
     *
     * @throws Exception
     */
    public function dbQuery($query)
    {
        if ($this->_mysqli === null) {
            throw new Exception(
                'MySQL not connected'
            );
        }
        return $this->_mysqli->query($query);
    }

    /**
     * Check if user is logged in to phpmyadmin
     *
     * @return boolean Where or not user is logged in
     */
    public function isLoggedIn()
    {
        return $this->isElementPresent('byXPath', '//*[@id="serverinfo"]/a[1]');
    }

    /**
     * Perform a logout, if logged in
     *
     * @return void
     */
    public function logOutIfLoggedIn()
    {
        if ($this->isLoggedIn()) {
            $this->byCssSelector("img.icon.ic_s_loggoff")->click();
        }
    }

    /**
     * Wait for an element to be present on the page
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     *
     * @return PHPUnit_Extensions_Selenium2TestCase_Element  Element waited for
     */
    public function waitForElement($func, $arg)
    {
        $this->timeouts()->implicitWait(10000);
        $element = call_user_func_array(
            array($this, $func), array($arg)
        );
        $this->timeouts()->implicitWait(0);
        return $element;
    }

    /**
     * Wait for an element to disappear
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     *
     * @return bool Whether or not the element disappeared
     */
    public function waitForElementNotPresent($func, $arg)
    {
        while (true) {
            if (!$this->isElementPresent($func, $arg)) {
                return true;
            }
            usleep(100);
        }
    }

    /**
     * Check if element is present or not
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     *
     * @return bool Whether or not the element is present
     */
    public function isElementPresent($func, $arg)
    {
        try {
            $element = call_user_func_array(
                array($this, $func), array($arg)
            );
        } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            // Element not present
            return false;
        }
        // Element Present
        return true;
    }

    /**
     * Get table cell data
     *
     * @param string $identifier Identifier: tableId.row.column
     *
     * @return text Data from the particular table cell
     */
    public function getTable($identifier)
    {
        list($tableID, $row, $column) = explode(".", $identifier);
        $sel = "table#{$tableID} tbody tr:nth-child({$row}) td:nth-child({$column})";
        $element = $this->byCssSelector(
            $sel
        );
        return $element->text();
    }

    /**
     * Type text in textarea (CodeMirror enabled)
     *
     * @param string $text Text to type
     *
     * @return void
     */
    public function typeInTextArea($text)
    {
        $text = str_replace(
            "(",
            PHPUnit_Extensions_Selenium2TestCase_Keys::SHIFT
            . PHPUnit_Extensions_Selenium2TestCase_Keys::NUMPAD9
            . PHPUnit_Extensions_Selenium2TestCase_Keys::NULL,
            $text
        );
        $this->byClassName("CodeMirror-scroll")->click();
        $this->keys($text);
    }
}
?>
