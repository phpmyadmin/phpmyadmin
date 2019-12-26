<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for user related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
declare(strict_types=1);

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
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfNotSuperUser();
        $this->_txtUsername = 'pma_user';
        $this->_txtPassword = 'abc_123';
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
        $this->waitForElement('partialLinkText', "User accounts")->click();

        // Let the User Accounts page load
        $this->waitAjax();

        $this->scrollIntoView('add_user_anchor');
        $this->waitForElement('id', 'usersForm');
        $ele = $this->waitForElement('id', "add_user_anchor");
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $userField = $this->waitForElement('name', "username");
        $userField->sendKeys($this->_txtUsername);

        $this->selectByLabel($this->byId("select_pred_hostname"), 'Local');

        $this->scrollIntoView('button_generate_password');
        $genButton = $this->waitForElement('id', 'button_generate_password');
        $genButton->click();

        $this->assertNotEquals("", $this->byId("text_pma_pw")->getAttribute('value'));
        $this->assertNotEquals("", $this->byId("text_pma_pw2")->getAttribute('value'));
        $this->assertNotEquals("", $this->byId("generated_pw")->getAttribute('value'));

        $this->byId("text_pma_pw")->sendKeys($this->_txtPassword);
        $this->byId("text_pma_pw2")->sendKeys($this->_txtPassword);

        // Make sure the element is visible before clicking
        $this->scrollIntoView('createdb-1');
        $this->waitForElement('id', 'createdb-1')->click();
        $this->waitForElement('id', 'createdb-2')->click();

        $this->scrollIntoView('addUsersForm_checkall');
        $this->byId("addUsersForm_checkall")->click();

        $this->scrollIntoView('adduser_submit');
        $this->waitForElement('id', "adduser_submit")->click();

        $success = $this->waitForElement('cssSelector', "div.success");
        $this->assertStringContainsString('You have added a new user', $success->getText());

        // Removing the newly added user
        $this->waitForElement('partialLinkText', "User accounts")->click();
        $el = $this->waitForElement('id', "usersForm");
        $temp = $this->_txtUsername . "&amp;#27;localhost";

        $this->byXPath(
            "(//input[@name='selected_usr[]'])[@value='" . $temp . "']"
        )->click();

        $this->scrollIntoView('fieldset_delete_user_footer');
        $this->byId("checkbox_drop_users_db")->click();

        $this->byId("buttonGo")->click();
        $this->waitForElement('cssSelector', "button.submitOK")->click();
        $this->acceptAlert();

        $success = $this->waitForElement('cssSelector', "div.success");
        $this->assertStringContainsString(
            'The selected users have been deleted',
            $success->getText()
        );
    }
}
