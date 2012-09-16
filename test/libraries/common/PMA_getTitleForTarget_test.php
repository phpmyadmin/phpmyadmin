<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::getTitleForTarget from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_getTitleForTarget_test extends PHPUnit_Framework_TestCase
{

    function dataProvider()
    {
        return array(
            array('tbl_structure.php', __('Structure')),
            array('tbl_sql.php', __('SQL'),),
            array('tbl_select.php', __('Search'),),
            array('tbl_change.php', __('Insert')),
            array('sql.php', __('Browse')),
            array('db_structure.php', __('Structure')),
            array('db_sql.php', __('SQL')),
            array('db_search.php', __('Search')),
            array('db_operations.php', __('Operations')),
        );
    }

    /**
     * @dataProvider dataProvider
     * @return void
     */
    function testGetTitleForTarget($target, $result)
    {
        $this->assertEquals(
            $result, PMA_Util::getTitleForTarget($target)
        );
    }

}
