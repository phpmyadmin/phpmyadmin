<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Display\CreateTable
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Display\CreateTable;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\Display\CreateTableTest class
 *
 * this class is for testing PhpMyAdmin\Display\CreateTable methods
 *
 * @package PhpMyAdmin-test
 */
class CreateTableTest extends TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['Server']['host'] = "localhost";
        $GLOBALS['cfg']['Server']['user'] = "pma_user";
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['PMA_PHP_SELF'] = "server_privileges.php";

        //$_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = "relation";
    }

    /**
     * Test for CreateTable::getHtml
     *
     * @return void
     */
    public function testPMAGetHtmlForCreateTable()
    {
        $db = "pma_db";

        //Call the test function
        $html = CreateTable::getHtml($db);

        //getImage
        $this->assertContains(
            Util::getImage('b_table_add'),
            $html
        );

        //__('Create table')
        $this->assertContains(
            __('Create table'),
            $html
        );

        //Url::getHiddenInputs
        $this->assertContains(
            Url::getHiddenInputs($db),
            $html
        );
        //label
        $this->assertContains(
            __('Name'),
            $html
        );
        $this->assertContains(
            __('Number of columns'),
            $html
        );

        //button
        $this->assertContains(
            __('Go'),
            $html
        );
    }
}
