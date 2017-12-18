<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestCase for table related tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

namespace PhpMyAdmin\Tests\Selenium;

/**
 * TableInsertTest class
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
class TableInsertTest extends TestBase
{
    /**
     * Setup the browser environment to run the selenium t
     * est case
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->dbQuery(
            "CREATE TABLE `test_table` ("
            . " `id` int(11) NOT NULL AUTO_INCREMENT,"
            . " `name` varchar(20) NOT NULL,"
            . " `datetimefield` datetime NOT NULL,"
            . " PRIMARY KEY (`id`)"
            . ")"
        );

    }

    /**
     * setUp function that can use the selenium session (called before each test)
     *
     * @return void
     */
    public function setUpPage()
    {
        parent::setUpPage();

        $this->login();
        $this->navigateTable('test_table');
    }

    /**
     * Insert data into table
     *
     * @return void
     *
     * @group large
     */
    public function testAddData()
    {
        if (mb_strtolower($this->getBrowser()) == 'safari') {
            /* TODO: this should be fixed, but the cause is unclear to me */
            $this->markTestIncomplete('Fails with Safari');
        }
        $this->waitAjax();
        $this->expandMore();

        $this->byPartialLinkText("Insert")->click();
        $this->waitAjax();
        $this->waitForElement("byId", "insertForm");

        $this->byId("field_3_3")->click();
        // shorter date to prevent error,
        // automatically gets appended with 00:00:00
        $this->keys("2011-01-2");

        $this->byId("field_1_3")->value("1");
        $this->byId("field_2_3")->value("abcd");

        $this->byId("field_6_3")->click();
        // shorter date to prevent error,
        // automatically gets appended with 00:00:00
        $this->keys("2012-01-2");

        $this->byId("field_5_3")->value("foo");

        $select = $this->select($this->byName("after_insert"));
        $select->selectOptionByLabel("Insert another new row");

        // post
        $this->byId("buttonYes")->click();
        $this->waitAjax();

        $ele = $this->waitForElement("byClassName", "success");
        $this->assertContains("2 rows inserted", $ele->text());

        $this->byId("field_3_3")->click();
        // shorter date to prevent error,
        // automatically gets appended with 00:00:00
        $this->keys("2013-01-2");
        $this->byId("field_2_3")->value("Abcd");

        // post
        $this->byCssSelector(
            "input[value=Go]"
        )->click();

        $this->waitAjax();

        // New message
        $ele = $this->waitForElement(
            'byXPath',
            "//div[contains(@class, 'success') and not(contains(@class, 'message'))]"
        );
        $this->assertContains("1 row inserted", $ele->text());

        $this->_assertDataPresent();
    }

    /**
     * Assert various data present in results table
     *
     * @return void
     */
    private function _assertDataPresent()
    {
        $this->byPartialLinkText("Browse")->click();

        $this->waitAjax();
        $this->waitForElement("byCssSelector", "table.table_results");

        $this->assertEquals(
            "1",
            $this->getCellByTableClass('table_results', 1, 5)
        );

        $this->assertEquals(
            "abcd",
            $this->getCellByTableClass('table_results', 1, 6)
        );

        $this->assertEquals(
            "2011-01-02 00:00:00",
            $this->getCellByTableClass('table_results', 1, 7)
        );

        $this->assertEquals(
            "2",
            $this->getCellByTableClass('table_results', 2, 5)
        );

        $this->assertEquals(
            "foo",
            $this->getCellByTableClass('table_results', 2, 6)
        );

        $this->assertEquals(
            "2012-01-02 00:00:00",
            $this->getCellByTableClass('table_results', 2, 7)
        );

        $this->assertEquals(
            "4",
            $this->getCellByTableClass('table_results', 3, 5)
        );

        $this->assertEquals(
            "Abcd",
            $this->getCellByTableClass('table_results', 3, 6)
        );

        $this->assertEquals(
            "2013-01-02 00:00:00",
            $this->getCellByTableClass('table_results', 3, 7)
        );
    }
}
