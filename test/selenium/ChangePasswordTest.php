<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use function trim;

/** @coversNothing */
class ChangePasswordTest extends TestBase
{
    /**
     * Create a test database for this test class
     */
    protected static bool $createDatabase = false;

    /**
     * Tests the changing of the password
     *
     * @group large
     */
    public function testChangePassword(): void
    {
        $this->login();

        $e = $this->waitForElement('id', 'change_password_anchor');
        $e->click();

        $this->waitAjax();

        $this->waitForElement('xpath', "//span[contains(., 'Change password')]");

        $ele = $this->waitForElement('name', 'pma_pw');
        $this->assertEquals('', $ele->getAttribute('value'));

        $ele = $this->waitForElement('name', 'pma_pw2');
        $this->assertEquals('', $ele->getAttribute('value'));

        $ele = $this->waitForElement('name', 'generated_pw');
        $this->assertEquals('', $ele->getAttribute('value'));

        $this->byId('button_generate_password')->click();
        $this->assertNotEquals('', $this->byName('pma_pw')->getAttribute('value'));
        $this->assertNotEquals('', $this->byName('pma_pw2')->getAttribute('value'));
        $this->assertNotEquals('', $this->byName('generated_pw')->getAttribute('value'));

        if ($this->getTestSuiteUserPassword() !== '') {
            $this->byName('pma_pw')->clear();
            $this->byName('pma_pw2')->clear();

            $this->byName('pma_pw')->click()->sendKeys($this->getTestSuiteUserPassword());

            $this->byName('pma_pw2')->click()->sendKeys($this->getTestSuiteUserPassword());
        } else {
            $this->byId('nopass_1')->click();
        }

        $this->byId('changePasswordGoButton')->click();
        $ele = $this->waitForElement('cssSelector', '.alert-success');
        $this->assertEquals(
            'The profile has been updated.',
            trim($ele->getText()),
        );
    }
}
