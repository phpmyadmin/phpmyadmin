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
                $cfgRelation['bookmarkwork'] = true;
                return $cfgRelation;
            }
        }

        if (! function_exists('PMA_DBI_fetch_result')) {
            function PMA_DBI_fetch_result()
            {
                return array(
                    'id' => 'id',
                    'label' => 'label'
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
            array(
                'user' => 'root',
                'db'   => 'phpmyadmin',
                'table'=> 'pma_bookmark'
            ),
            PMA_Bookmark_getParams()
        );
    }

    /**
     * Test for PMA_Bookmark_getList
     */
    public function testPMA_Bookmark_getList(){
        $this->assertEquals(
            array(
                'id' => 'id (shared)',
                'label' => 'label (shared)'
            ),
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
                return "SELECT query FROM `phpmyadmin`.`pma_bookmark` WHERE dbase = 'phpmyadmin' AND (user = 'root' OR user = '') AND `id` = 1";
            }
        }
        $this->assertEquals(
            "SELECT query FROM `phpmyadmin`.`pma_bookmark` WHERE dbase = 'phpmyadmin' AND (user = 'root' OR user = '') AND `id` = 1",
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
                return true;
            }
        }
        $this->assertTrue(
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
                return true;
            }
        }
        $this->assertTrue(
            PMA_Bookmark_delete('phpmyadmin', '1')
        );
    }
}
