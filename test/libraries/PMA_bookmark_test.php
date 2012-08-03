<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for bookmark.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_bookmark_test extends PHPUnit_Framework_TestCase
{

    public function setUp(){

        if (! function_exists('PMA_getRelationsParam')) {
            function PMA_getRelationsParam()
            {
                $cfgRelation['bookmarkwork'] = false;
                return $cfgRelation;
            }
        }

        if (! function_exists('PMA_DBI_fetch_result')) {
            function PMA_DBI_fetch_result()
            {
                return array(
                    'table1',
                    'table2'
                );
            }
        }

        if (! defined('PMA_DBI_QUERY_STORE')) {
            define('PMA_DBI_QUERY_STORE', 1);
        }

        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma_bookmark';
        $GLOBALS['server'] = 1;

        require_once 'libraries/bookmark.lib.php';
    }
    /**
     * Test for PMA_Bookmark_getParams
     */
    public function testPMA_Bookmark_getParams(){

        $this->assertEquals(
            false,
            PMA_Bookmark_getParams()
        );
    }

    /**
     * Test for PMA_Bookmark_getList
     */
    public function testPMA_Bookmark_getList(){
        $this->assertEquals(
            array(),
            PMA_Bookmark_getList('phpmyadmin')
        );
    }

    /**
     * Test for PMA_Bookmark_get
     */
    public function testPMA_Bookmark_get(){
        if (! function_exists('PMA_DBI_fetch_value')) {
            function PMA_DBI_fetch_value()
            {
                return '';
            }
        }
        $this->assertEquals(
            '',
            PMA_Bookmark_get('phpmyadmin', '1')
        );
    }

    /**
     * Test for PMA_Bookmark_save
     */
    public function testPMA_Bookmark_save(){
        if (! function_exists('PMA_DBI_query')) {
            function PMA_DBI_query()
            {
                return false;
            }
        }
        $this->assertfalse(
            PMA_Bookmark_save(array(
                'dbase' => 'phpmyadmin',
                'user' => 'phpmyadmin',
                'query' => 'SELECT "phpmyadmin"',
                'label' => 'phpmyadmin',
            ))
        );
    }

    /**
     * Test for PMA_Bookmark_delete
     */
    public function testPMA_Bookmark_delete(){
        if (! function_exists('PMA_DBI_try_query')) {
            function PMA_DBI_try_query()
            {
                return false;
            }
        }
        $this->assertFalse(
            PMA_Bookmark_delete('phpmyadmin', '1')
        );
    }
}
