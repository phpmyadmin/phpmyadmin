<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

use function bin2hex;
use function random_bytes;

#[CoversNothing]
#[Large]
class CreateRemoveUserTest extends TestBase
{
    /**
     * Create a test database for this test class
     */
    protected static bool $createDatabase = false;

    /**
     * Username for the user
     */
    private string $txtUsername;

    /**
     * Password for the user
     */
    private string $txtPassword;

    /**
     * Setup the browser environment to run the selenium test case
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfNotSuperUser();
        $this->txtUsername = 'test_user_' . bin2hex(random_bytes(4));
        $this->txtPassword = 'abc_123';
        $this->login();
    }

    /**
     * Creates and removes a user
     */
    public function testCreateRemoveUser(): void
    {
        $this->waitForElement('partialLinkText', 'User accounts')->click();

        // Let the User Accounts page load
        $this->waitAjax();

        $this->scrollIntoView('add_user_anchor');
        $this->waitForElement('id', 'usersForm');
        $ele = $this->waitForElement('id', 'add_user_anchor');
        $this->moveto($ele);
        $ele->click();

        $this->waitAjax();
        $userField = $this->waitForElement('name', 'username');
        $userField->sendKeys($this->txtUsername);

        $this->selectByLabel($this->byId('select_pred_hostname'), 'Local');

        $this->scrollIntoView('button_generate_password');
        $genButton = $this->waitForElement('id', 'button_generate_password');
        $genButton->click();

        self::assertNotSame('', $this->byId('text_pma_pw')->getAttribute('value'));
        self::assertNotSame('', $this->byId('text_pma_pw2')->getAttribute('value'));
        self::assertNotSame('', $this->byId('generated_pw')->getAttribute('value'));

        $this->byId('text_pma_pw')->sendKeys($this->txtPassword);
        $this->byId('text_pma_pw2')->sendKeys($this->txtPassword);

        // Make sure the element is visible before clicking
        $this->scrollIntoView('createdb-1');
        $this->waitForElement('id', 'createdb-1')->click();
        $this->waitForElement('id', 'createdb-2')->click();

        $this->scrollIntoView('addUsersForm_checkall');
        $this->byId('addUsersForm_checkall')->click();

        $this->scrollIntoView('adduser_submit');
        $this->waitForElement('id', 'adduser_submit')->click();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString('You have added a new user', $success->getText());

        // Removing the newly added user
        $this->waitForElement('partialLinkText', 'User accounts')->click();
        $this->waitForElement('id', 'usersForm');
        $temp = $this->txtUsername . '&amp;#27;localhost';

        $this->byXPath("(//input[@name='selected_usr[]'])[@value='" . $temp . "']")->click();

        $this->scrollIntoView('deleteUserCard');
        $this->byId('dropUsersDbCheckbox')->click();

        $this->byId('buttonGo')->click();
        $this->waitForElement('id', 'functionConfirmOkButton')->click();
        $this->acceptAlert();

        $success = $this->waitForElement('cssSelector', '.alert-success');
        self::assertStringContainsString(
            'The selected users have been deleted',
            $success->getText(),
        );
    }
}
