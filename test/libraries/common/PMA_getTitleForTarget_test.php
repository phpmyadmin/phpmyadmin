<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::getTitleForTarget from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 ** Test for PMA\libraries\Util::getTitleForTarget from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_GetTitleForTarget_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testGetTitleForTarget
     *
     * @return array
     */
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
     * Test for
     *
     * @param string $target Target
     * @param array  $result Expected value
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    function testGetTitleForTarget($target, $result)
    {
        $this->assertEquals(
            $result, PMA\libraries\Util::getTitleForTarget($target)
        );
    }

}
