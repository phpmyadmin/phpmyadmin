<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for user related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

/**
 * PmaSeleniumCreateRemoveUserTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumCreateRemoveUserTest extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * Username for the user
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
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
        $this->_txtUsername = 'pma_user';
        $this->_txtPassword = 'abc_123';
    }

    /**
     * Creates and removes a user
     *
     * @return void
     */
    public function testCreateRemoveUser()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click("link=Users");
        $this->waitForElementPresent("fieldset_add_user");
        $this->click("link=Add user");
        $this->waitForElementPresent("fieldset_add_user_login");
        $this->type("name=username", $this->_txtUsername);
        $this->select("id=select_pred_hostname", "label=Local");
        $this->click("id=button_generate_password");
        $this->assertNotEquals("", $this->getValue("text_pma_pw"));
        $this->assertNotEquals("", $this->getValue("text_pma_pw2"));
        $this->assertNotEquals("", $this->getValue("generated_pw"));
        $this->type("id=text_pma_pw", $this->_txtPassword);
        $this->type("id=text_pma_pw2", $this->_txtPassword);
        $this->waitForElementPresent("fieldset_add_user_database");
        $this->click("id=createdb-1");
        $this->click("id=createdb-2");
        $this->waitForElementPresent("fieldset_user_global_rights");
        $this->click("link=Check All");
        $this->waitForElementPresent("fieldset_add_user_footer");
        $this->click("name=adduser_submit");
        $this->waitForElementPresent("css=span.ajax_notification");
        $this->assertElementPresent("css=span.ajax_notification div.success");
        $this->waitForElementPresent("usersForm");
        $temp = $this->_txtUsername."&amp;#27;localhost";
        $this->click(
            "xpath=(//input[@name='selected_usr[]'])[@value='".$temp."']"
        );
        $this->click("id=checkbox_drop_users_db");
        $this->getConfirmation();
        $this->click("id=buttonGo");
        $this->waitForElementPresent("css=span.ajax_notification");
        $this->assertElementPresent("css=span.ajax_notification div.success");
    }
}
