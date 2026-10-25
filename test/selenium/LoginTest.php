<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;

/**
 * @coversNothing
 * @large
 */
#[CoversNothing]
#[Large]
class LoginTest extends TestBase
{
    /**
     * Create a test database for this test class
     *
     * @var bool
     */
    protected $createDatabase = false;

    /**
     * Login before starting this test
     *
     * @var bool
     */
    protected $login = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for successful login
     */
    public function testSuccessfulLogin(): void
    {
        $this->login();
        $this->waitForElement('xpath', '//*[@id="server-breadcrumb"]');
        self::assertTrue($this->isSuccessLogin());
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     */
    public function testLoginWithWrongPassword(): void
    {
        $this->login('Admin', 'Admin');
        $this->waitForElement('xpath', '//*[@class="alert alert-danger" and contains(.,\'Access denied for\')]');
        self::assertTrue($this->isUnsuccessLogin());
    }
}
