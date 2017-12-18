<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for normalization
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * PMA_SeleniumNormalizationTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class NormalizationTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium test case
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `val` int(11) NOT NULL,"
            . " `val2` varchar(64) NOT NULL,"
            . "PRIMARY KEY(id)"
            . ")"
        );
    }

    /**
     * setUp function that can use the selenium session
     *
     * @return void
     */
    public function setUpPage()
    {
        parent::setUpPage();

        $this->login();
        $this->navigateTable('test_table');
        $this->waitForElement(
            "byXPath",
            "(//a[contains(., 'Structure')])"
        )->click();

        $this->waitAjax();

        $this->waitForElement("byId", "tablestructure");
        $this->byPartialLinkText('Normalize')->click();
        $this->waitForElement("byId", "normalizeTable");
    }

    /**
     * Test for normalization to 1NF
     *
     * @return void
     *
     * @group large
     */
    public function testNormalizationTo1NF()
    {
        $this->assertTrue(
            $this->isElementPresent('byCssSelector', 'fieldset')
        );
        $this->assertEquals(
            "First step of normalization (1NF)",
            $this->byCssSelector('label[for=normalizeTo_1nf]')->text()
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', 'input[id=normalizeTo_1nf][type=radio]:checked'
            )
        );
        $this->byCssSelector('input[name=submit_normalize]')->click();
        $this->waitForElement('byId', 'mainContent');
        $this->_test1NFSteps();
    }

    /**
     * assertions in 1NF steps 1.1, 1.2, 1.3
     *
     * @return void
     */
    private function _test1NFSteps()
    {
        $this->assertEquals(
            "First step of normalization (1NF)",
            $this->byCssSelector('#page_content h3')->text()
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#mainContent h4'
            )
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#mainContent #newCols'
            )
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '.tblFooters'
            )
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#selectNonAtomicCol option[value=val2]'
            )
        );
        $this->assertFalse(
            $this->isElementPresent(
                'byCssSelector', '#selectNonAtomicCol option[value=val]'
            )
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#selectNonAtomicCol option[value=no_such_col]'
            )
        );
        $this->select(
            $this->byId('selectNonAtomicCol')
        )->selectOptionByValue('no_such_col');
        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Step 1.2 Have a primary key')]"
        );
        $text = $this->byCssSelector("#mainContent h4")->text();
        $this->assertContains("Primary key already exists.", $text);
        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Step 1.3 Move repeating groups')]"
        );
        $this->byCssSelector('input[value="No repeating group"]')->click();
        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'Step 1.4 Remove redundant columns')]"
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#mainContent #extra'
            )
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#extra input[value=val2][type=checkbox]'
            )
        );
        $this->assertTrue(
            $this->isElementPresent(
                'byCssSelector', '#extra input[value=id][type=checkbox]'
            )
        );
        $this->byCssSelector('#extra input[value=val][type=checkbox]')->click();
        $this->byCssSelector("#removeRedundant")->click();
        $this->waitForElement(
            "byXPath",
            "//legend[contains(., 'End of step')]"
        );
        $this->assertContains(
            "The first step of normalization is complete for table 'test_table'.",
            $this->byCssSelector("#mainContent h4")->text()
        );
    }
}
