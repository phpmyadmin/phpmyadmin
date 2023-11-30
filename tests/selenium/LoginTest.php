<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[CoversNothing]
class LoginTest extends TestBase
{
    /**
     * Create a test database for this test class
     */
    protected static bool $createDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logOutIfLoggedIn();
    }

    /**
     * Test for successful login
     */
    #[Group('large')]
    public function testSuccessfulLogin(): void
    {
        $this->login();
        $this->waitForElement('xpath', '//*[@id="server-breadcrumb"]');
        $this->assertTrue($this->isSuccessLogin());
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     */
    #[Group('large')]
    public function testLoginWithWrongPassword(): void
    {
        $this->login('Admin', 'Admin');
        $this->waitForElement('xpath', '//*[@class="alert alert-danger" and contains(.,\'Access denied for\')]');
        $this->assertTrue($this->isUnsuccessLogin());
    }
}
