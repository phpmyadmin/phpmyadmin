<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium parent class for TestCases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

/**
 * PmaSeleniumTestCase class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumTestCase
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
     * @var object
     */
    private $_selenium;

    /**
     * Configuration Instance
     *
     * @access private
     * @var object
     */
    private $_config;

    /**
     * constructor
     *
     * @param object $selenium Selenium Context
     */
    public function __construct($selenium)
    {
        $this->_txtUsername = 'input_username';
        $this->_txtPassword = 'input_password';
        $this->_btnLogin = 'input_go';
        $this->_config = new TestConfig();
        $this->_selenium = $selenium;
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
        $this->_selenium->open($this->_config->getLoginURL());
        $this->_selenium->type($this->_txtUsername, $username);
        $this->_selenium->type($this->_txtPassword, $password);
        $this->_selenium->click($this->_btnLogin);
        $this->_selenium->waitForPageToLoad($this->_config->getTimeoutValue());
    }

    /**
     * Checks whether the login is successful
     *
     * @return boolean
     */
    public function isSuccessLogin()
    {
        if ($this->_selenium->isElementPresent("//*[@id=\"serverinfo\"]")) {
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
        if ($this->_selenium->isElementPresent("css=div.error")) {
            return true;
        } else {
            return false;
        }
    }
}
?>
