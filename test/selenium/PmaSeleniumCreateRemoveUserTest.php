<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for user related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'Helper.php';

/**
 * PmaSeleniumCreateRemoveUserTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class PmaSeleniumCreateRemoveUserTest extends PHPUnit_Extensions_Selenium2TestCase
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
        $this->_helper->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->byLinkText("Users")->click();

        $link = $this->_helper->waitForElement("byLinkText", "Add user");
        $link->click();

        $userField = $this->_helper->waitForElement("byName", "username");
        $userField->value($this->_txtUsername);

        $select = $this->select($this->byId("select_pred_hostname"));
        $select->selectOptionByLabel("Local");

        $this->byId("button_generate_password")->click();
        $this->assertNotEquals("", $this->byId("text_pma_pw")->value());
        $this->assertNotEquals("", $this->byId("text_pma_pw2")->value());
        $this->assertNotEquals("", $this->byId("generated_pw")->value());

        $this->byId("text_pma_pw")->value($this->_txtPassword);
        $this->byId("text_pma_pw2")->value($this->_txtPassword);
        $this->byId("createdb-1")->click();
        $this->byId("createdb-2")->click();
        $this->byId("addUsersForm_checkall")->click();
        $this->byName("adduser_submit")->click();

        $success = $this->_helper->waitForElement("byCssSelector", "div.success");
        $this->assertContains('You have added a new user', $success->text());

        $el = $this->_helper->waitForElement("byId", "usersForm");
        $temp = $this->_txtUsername."&amp;#27;localhost";

        $this->byXPath(
            "(//input[@name='selected_usr[]'])[@value='".$temp."']"
        )->click();

        $this->byId("checkbox_drop_users_db")->click();
        $this->acceptAlert();
        $this->byId("buttonGo")->click();

        $success = $this->_helper->waitForElement("byCssSelector", "div.success");
        $this->assertContains(
            'The selected users have been deleted',
            $success->text()
        );
    }
}
