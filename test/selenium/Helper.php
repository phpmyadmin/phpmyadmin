<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium helper class for TestCases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'TestConfig.php';

/**
 * Helper class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class Helper
{
    /**
     * Username of the user
     *
     * @access private
     * @var string
     */
    private $_txtUsername;

    /**
     * Password for the user
     *
     * @access private
     * @var string
     */
    private $_txtPassword;

    /**
     * id of the login button
     *
     * @access private
     * @var string
     */
    private $_btnLogin;

    /**
     * Selenium Context
     *
     * @access private
     * @var PHPUnit_Extensions_Selenium2TestCase
     */
    private $_selenium;

    /**
     * Configuration Instance
     *
     * @access private
     * @var TestConfig
     */
    private $_config;

    /**
     * mysqli object
     *
     * @access private
     * @var mysqli
     */
    private $_mysqli;

    /**
     * constructor
     *
     * @param PHPUnit_Extensions_Selenium2TestCase $selenium Selenium Context
     */
    public function __construct(PHPUnit_Extensions_Selenium2TestCase $selenium)
    {
        $this->_txtUsername = 'input_username';
        $this->_txtPassword = 'input_password';
        $this->_btnLogin = 'input_go';
        $this->_config = new TestConfig();
        $this->_selenium = $selenium;
        $this->_mysqli = null;
    }

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
        $this->_selenium->url($this->_config->getLoginURL());
        $usernameField = $this->_selenium->byId($this->_txtUsername);
        $usernameField->value($username);
        $passwordField = $this->_selenium->byId($this->_txtPassword);
        $passwordField->value($password);
        $this->_selenium->byId($this->_btnLogin)->click();
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
        $e = $this->_selenium->byPartialLinkText("Server: ");
        $e->click();
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');
    }

    /**
     * Establishes a connection with the local database
     *
     * @return void
     */
    public function dbConnect()
    {
        if ($this->_mysqli === null) {
            list($user, $pass) = $this->_config->getDBCredentials();
            $this->_mysqli = new mysqli("localhost", $user, $pass);
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
     * @return void
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
            $this->_selenium->byCssSelector("img.icon.ic_s_loggoff")->click();
        }
    }

    /**
     * Get current browser string
     *
     * @return string Browser String
     */
    public function getBrowserString()
    {
        $browserString = $this->_config->getCurrentBrowser();
        return $browserString;
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
        $this->_selenium->timeouts()->implicitWait(10000);
        $element = call_user_func_array(
            array($this->_selenium, $func), array($arg)
        );
        $this->_selenium->timeouts()->implicitWait(0);
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
                array($this->_selenium, $func), array($arg)
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
        $element = $this->_selenium->byCssSelector(
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
        $this->_selenium->byClassName("CodeMirror-scroll")->click();
        $this->_selenium->keys($text);
    }
}
?>
