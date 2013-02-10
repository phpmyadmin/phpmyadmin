<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for login related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
require_once 'PmaSeleniumTestCase.php';
require_once 'Helper.php';

class PmaSeleniumCreateDropDatabaseTest extends PHPUnit_Extensions_SeleniumTestCase
{

    public function setUp()
    {
        $helper = new Helper();
        $this->setBrowser(Helper::getBrowserString());
        $this->setBrowserUrl(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
    }

    public function testCreateDropDatabase()
    {
        $log = new PmaSeleniumTestCase($this);
        $log->login(TESTSUITE_USER, TESTSUITE_PASSWORD);
        $this->click("link=Databases");
		sleep(10);
    	$this->type("id=text_create_db", "pma_testdb");
    	$this->click("id=buttonGo");
		sleep(10);
		$this->assertTrue($this->isTextPresent("pma_testdb"));		
    	
    	$this->click("xpath=(//input[@name='selected_dbs[]'])[@value='pma_testdb']");
    	$this->click("css=button.mult_submit.ajax");
    	$this->click("//button[@type='button']");
    	$this->click("css=img.icon.ic_b_home");
		sleep(10);
		$this->assertFalse($this->isTextPresent("pma_testdb"));
    }
}
