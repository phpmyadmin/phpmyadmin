<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium parent class for TestCases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

class PmaSeleniumTestCase
{
    private $_txtUsername;
    private $_txtPassword;
    private $_btnLogin;
    private $_selenium;
    private $_config;

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
    *
    * @return boolean
    */
    public function isUnsuccessLogin()
    {
        $val = $this->_selenium->getValue('input_go');
        if ($this->_selenium->isElementPresent("//html/body/div/div[@class='error']")) {
            return true;
        } else {
            return false;
        }
    }
}
?>
