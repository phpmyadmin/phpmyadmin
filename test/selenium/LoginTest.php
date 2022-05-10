<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * @coversNothing
 */
class LoginTest extends TestBase
{
    /**
     * Create a test database for this test class
     *
     * @var bool
     */
    protected static $createDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for successful login
     *
     * @group large
     */
    public function testSuccessfulLogin(): void
    {
        $this->login();
        $this->waitForElement('xpath', '//*[@id="server-breadcrumb"]');
        $this->assertTrue($this->isSuccessLogin());
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     *
     * @group large
     */
    public function testLoginWithWrongPassword(): void
    {
        $this->login('Admin', 'Admin');
        $this->waitForElement('xpath', '//*[@class="alert alert-danger" and contains(.,\'Access denied for\')]');
        $this->assertTrue($this->isUnsuccessLogin());
    }
}
