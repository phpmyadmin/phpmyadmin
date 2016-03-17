<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA\libraries\Util::buildActionTitles from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;




/**
 * Test for PMA\libraries\Util::buildActionTitles from common.lib
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
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $GLOBALS['cfg'] = array('ActionLinksMode' => 'both');
        $GLOBALS['pmaThemeImage'] = 'theme/';
    }

    /**
     * Test for buildActionTitles
     *
     * @return void
     */
    function testBuildActionTitles()
    {
        $titles = array();
        $titles['Browse']     = PMA\libraries\Util::getIcon('b_browse.png', __('Browse'));
        $titles['NoBrowse']   = PMA\libraries\Util::getIcon('bd_browse.png', __('Browse'));
        $titles['Search']     = PMA\libraries\Util::getIcon('b_select.png', __('Search'));
        $titles['NoSearch']   = PMA\libraries\Util::getIcon('bd_select.png', __('Search'));
        $titles['Insert']     = PMA\libraries\Util::getIcon('b_insrow.png', __('Insert'));
        $titles['NoInsert']   = PMA\libraries\Util::getIcon('bd_insrow.png', __('Insert'));
        $titles['Structure']  = PMA\libraries\Util::getIcon('b_props.png', __('Structure'));
        $titles['Drop']       = PMA\libraries\Util::getIcon('b_drop.png', __('Drop'));
        $titles['NoDrop']     = PMA\libraries\Util::getIcon('bd_drop.png', __('Drop'));
        $titles['Empty']      = PMA\libraries\Util::getIcon('b_empty.png', __('Empty'));
        $titles['NoEmpty']    = PMA\libraries\Util::getIcon('bd_empty.png', __('Empty'));
        $titles['Edit']       = PMA\libraries\Util::getIcon('b_edit.png', __('Edit'));
        $titles['NoEdit']     = PMA\libraries\Util::getIcon('bd_edit.png', __('Edit'));
        $titles['Export']     = PMA\libraries\Util::getIcon('b_export.png', __('Export'));
        $titles['NoExport']   = PMA\libraries\Util::getIcon('bd_export.png', __('Export'));
        $titles['Execute']    = PMA\libraries\Util::getIcon('b_nextpage.png', __('Execute'));
        $titles['NoExecute']  = PMA\libraries\Util::getIcon('bd_nextpage.png', __('Execute'));
        $titles['Favorite']   = PMA\libraries\Util::getIcon('b_favorite.png', '');
        $titles['NoFavorite'] = PMA\libraries\Util::getIcon('b_no_favorite.png', '');

        $this->assertEquals($titles, PMA\libraries\Util::buildActionTitles());

    }
}
