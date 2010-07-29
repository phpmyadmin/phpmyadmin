<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package phpMyAdmin-test
 */

require_once('PmaSeleniumTestCase.php');


class PmaSeleniumLoginTest extends PmaSeleniumTestCase 
{
    public function testLogin()
    {
        $this->doLogin();
        $this->assertRegExp("/phpMyAdmin .*-dev/", $this->getTitle());
    }
}
?>
