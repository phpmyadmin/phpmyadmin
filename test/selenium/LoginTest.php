<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * @coversNothing
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
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
     *
     * @group large
     */
    #[\PHPUnit\Framework\Attributes\Group('large')]
    public function testSuccessfulLogin(): void
    {
        $this->login();
        $this->waitForElement('xpath', '//*[@id="server-breadcrumb"]');
        self::assertTrue($this->isSuccessLogin());
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     *
     * @group large
     */
    #[\PHPUnit\Framework\Attributes\Group('large')]
    public function testLoginWithWrongPassword(): void
    {
        $this->login('Admin', 'Admin');
        $this->waitForElement('xpath', '//*[@class="alert alert-danger" and contains(.,\'Access denied for\')]');
        self::assertTrue($this->isUnsuccessLogin());
    }
}
