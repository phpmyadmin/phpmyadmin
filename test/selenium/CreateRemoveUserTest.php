<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for user related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * CreateRemoveUserTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class CreateRemoveUserTest extends TestBase
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
        parent::setUp();
        $this->skipIfNotSuperUser();
        $this->_txtUsername = 'pma_user';
        $this->_txtPassword = 'abc_123';
    }

    public function setUpPage()
    {
        parent::setUpPage();
        $this->login();
    }

    /**
     * Creates and removes a user
     *
     * @return void
     *
     * @group large
     */
    public function testCreateRemoveUser()
    {
        $this->waitForElement('byPartialLinkText', "User accounts")->click();

        // Let the User Accounts page load
        $this->waitAjax();

        $this->scrollIntoView('add_user_anchor');
        $this->waitForElement('byId', 'usersForm');
        $ele = $this->waitForElement("byId", "add_user_anchor");
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $userField = $this->waitForElement("byName", "username");
        $userField->value($this->_txtUsername);

        $select = $this->select($this->byId("select_pred_hostname"));
        $select->selectOptionByLabel("Local");

        $this->scrollIntoView('button_generate_password');
        $genButton = $this->waitForElement('byId', 'button_generate_password');
        $genButton->click();

        $this->assertNotEquals("", $this->byId("text_pma_pw")->value());
        $this->assertNotEquals("", $this->byId("text_pma_pw2")->value());
        $this->assertNotEquals("", $this->byId("generated_pw")->value());

        $this->byId("text_pma_pw")->value($this->_txtPassword);
        $this->byId("text_pma_pw2")->value($this->_txtPassword);

        // Make sure the element is visible before clicking
        $this->scrollIntoView('createdb-1');
        $this->waitForElement('byId', 'createdb-1')->click();
        $this->waitForElement('byId', 'createdb-2')->click();

        $this->scrollIntoView('addUsersForm_checkall');
        $this->byId("addUsersForm_checkall")->click();

        $this->scrollIntoView('adduser_submit');
        $this->waitForElement('byId', "adduser_submit")->click();

        $success = $this->waitForElement("byCssSelector", "div.success");
        $this->assertContains('You have added a new user', $success->text());

        // Removing the newly added user
        $this->waitForElement('byPartialLinkText', "User accounts")->click();
        $el = $this->waitForElement("byId", "usersForm");
        $temp = $this->_txtUsername . "&amp;#27;localhost";

        $this->byXPath(
            "(//input[@name='selected_usr[]'])[@value='" . $temp . "']"
        )->click();

        $this->scrollIntoView('fieldset_delete_user_footer');
        $this->byId("checkbox_drop_users_db")->click();

        $this->byId("buttonGo")->click();
        $this->waitForElement("byCssSelector", "button.submitOK")->click();
        $this->acceptAlert();

        $success = $this->waitForElement("byCssSelector", "div.success");
        $this->assertContains(
            'The selected users have been deleted',
            $success->text()
        );
    }
}
