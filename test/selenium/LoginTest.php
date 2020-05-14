<?php
/**
 * Selenium TestCase for login related tests
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

/**
 * LoginTest class
 *
 * @group      selenium
 */
class LoginTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for successful login
     *
     * @return void
     *
     * @group large
     */
    public function testSuccessfulLogin()
    {
        $this->login();
        $this->waitForElement('xpath', '//*[@id="server-breadcrumb"]');
        $this->assertTrue($this->isSuccessLogin());
        $this->logOutIfLoggedIn();
    }

    /**
     * Test for unsuccessful login
     *
     * @return void
     *
     * @group large
     */
    public function testLoginWithWrongPassword()
    {
        $this->login('Admin', 'Admin');
        $this->waitForElement('xpath', '//*[@class="alert alert-danger" and contains(.,\'Access denied for\')]');
        $this->assertTrue($this->isUnsuccessLogin());
    }
}
