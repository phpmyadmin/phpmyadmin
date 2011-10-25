<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package PhpMyAdmin-test
 * @group Selenium
 */

require_once 'PmaSeleniumTestCase.php';


class PmaSeleniumLoginTest extends PmaSeleniumTestCase
{
    protected $captureScreenshotOnFailure = true;
    protected $screenshotPath = '/var/www/screenshots';
    protected $screenshotUrl = 'http://localhost/screenshots';

    public function testLogin()
    {
        $this->doLogin();

        // Check if login error happend
        if ($this->isElementPresent("//html/body/div/div[@class='error']")) {
            $this->fail($this->getText("//html/body/div/div[@class='error']"));
        }

        // Check server info heder is present //*[@id="serverinfo"]
        for ($second = 0;; $second++) {
            if ($second >= 60)
                $this->fail("Timeout waiting main page to load!");
            try {
                if ($this->isElementPresent("//*[@id=\"serverinfo\"]"))
                    break;
            } catch (Exception $e) {
                $this->fail("Exception: ".$e->getMessage());
            }
            sleep(1);
        }

    }
}
?>
