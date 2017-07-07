<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Util::buildActionTitles from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
use PhpMyAdmin\Theme;




/**
 * Test for PhpMyAdmin\Util::buildActionTitles from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_BuildActionTitles_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['cfg'] = array('ActionLinksMode' => 'both');
    }

    /**
     * Test for buildActionTitles
     *
     * @return void
     */
    function testBuildActionTitles()
    {
        $titles = array();
        $titles['Browse']     = PhpMyAdmin\Util::getIcon('b_browse.png', __('Browse'));
        $titles['NoBrowse']   = PhpMyAdmin\Util::getIcon('bd_browse.png', __('Browse'));
        $titles['Search']     = PhpMyAdmin\Util::getIcon('b_select.png', __('Search'));
        $titles['NoSearch']   = PhpMyAdmin\Util::getIcon('bd_select.png', __('Search'));
        $titles['Insert']     = PhpMyAdmin\Util::getIcon('b_insrow.png', __('Insert'));
        $titles['NoInsert']   = PhpMyAdmin\Util::getIcon('bd_insrow.png', __('Insert'));
        $titles['Structure']  = PhpMyAdmin\Util::getIcon('b_props.png', __('Structure'));
        $titles['Drop']       = PhpMyAdmin\Util::getIcon('b_drop.png', __('Drop'));
        $titles['NoDrop']     = PhpMyAdmin\Util::getIcon('bd_drop.png', __('Drop'));
        $titles['Empty']      = PhpMyAdmin\Util::getIcon('b_empty.png', __('Empty'));
        $titles['NoEmpty']    = PhpMyAdmin\Util::getIcon('bd_empty.png', __('Empty'));
        $titles['Edit']       = PhpMyAdmin\Util::getIcon('b_edit.png', __('Edit'));
        $titles['NoEdit']     = PhpMyAdmin\Util::getIcon('bd_edit.png', __('Edit'));
        $titles['Export']     = PhpMyAdmin\Util::getIcon('b_export.png', __('Export'));
        $titles['NoExport']   = PhpMyAdmin\Util::getIcon('bd_export.png', __('Export'));
        $titles['Execute']    = PhpMyAdmin\Util::getIcon('b_nextpage.png', __('Execute'));
        $titles['NoExecute']  = PhpMyAdmin\Util::getIcon('bd_nextpage.png', __('Execute'));
        $titles['Favorite']   = PhpMyAdmin\Util::getIcon('b_favorite.png', '');
        $titles['NoFavorite'] = PhpMyAdmin\Util::getIcon('b_no_favorite.png', '');

        $this->assertEquals($titles, PhpMyAdmin\Util::buildActionTitles());

    }
}
