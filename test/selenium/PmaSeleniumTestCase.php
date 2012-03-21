<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium parent class for TestCases
 *
 * @package PhpMyAdmin-test
 * @group Selenium
 */

class PmaSeleniumTestCase
{
    private $txtUsername;
    private $txtPassword;
    private $btnLogin;
    private $selenium;
    private $config;

    public function __construct($selenium) {
        $this->txtUsername = 'input_username';
        $this->txtPassword = 'input_password';
        $this->btnLogin = 'input_go';
	$this->config = new TestConfig();
        $this->selenium = $selenium;

    }

     /**
     * perform a login
     * @param <type> $username
     * @param <type> $password
     */
    public function login($username, $password) {

        $this->selenium->open($this->config->getLoginURL());
        $this->selenium->type($this->txtUsername, $username);
        $this->selenium->type($this->txtPassword, $password);
        $this->selenium->click($this->btnLogin);
        $this->selenium->waitForPageToLoad($this->config->getTimeoutValue());
        
    }

    /**
     *
     * @return boolean
     */
    public function isSuccessLogin() {
	    if($this->selenium->isElementPresent("//*[@id=\"serverinfo\"]")){
		    return true;
	    } else {
		    return false;
	    }
    }
    
    /**
     *
     * @return boolean
     */
    public function isUnsuccessLogin() {
	    $val = $this->selenium->getValue('input_go');
	    if($this->selenium->isElementPresent("//html/body/div/div[@class='error']")){
		    return true;
	    } else {
		    return false;
	    }
    }

}

?>
