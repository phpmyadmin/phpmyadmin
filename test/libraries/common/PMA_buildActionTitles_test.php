<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Util::buildActionTitles from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Test for PMA_Util::buildActionTitles from common.lib
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
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
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
        $titles['Browse']     = PMA_Util::getIcon('b_browse.png', __('Browse'));
        $titles['NoBrowse']   = PMA_Util::getIcon('bd_browse.png', __('Browse'));
        $titles['Search']     = PMA_Util::getIcon('b_select.png', __('Search'));
        $titles['NoSearch']   = PMA_Util::getIcon('bd_select.png', __('Search'));
        $titles['Insert']     = PMA_Util::getIcon('b_insrow.png', __('Insert'));
        $titles['NoInsert']   = PMA_Util::getIcon('bd_insrow.png', __('Insert'));
        $titles['Structure']  = PMA_Util::getIcon('b_props.png', __('Structure'));
        $titles['Drop']       = PMA_Util::getIcon('b_drop.png', __('Drop'));
        $titles['NoDrop']     = PMA_Util::getIcon('bd_drop.png', __('Drop'));
        $titles['Empty']      = PMA_Util::getIcon('b_empty.png', __('Empty'));
        $titles['NoEmpty']    = PMA_Util::getIcon('bd_empty.png', __('Empty'));
        $titles['Edit']       = PMA_Util::getIcon('b_edit.png', __('Edit'));
        $titles['NoEdit']     = PMA_Util::getIcon('bd_edit.png', __('Edit'));
        $titles['Export']     = PMA_Util::getIcon('b_export.png', __('Export'));
        $titles['NoExport']   = PMA_Util::getIcon('bd_export.png', __('Export'));
        $titles['Execute']    = PMA_Util::getIcon('b_nextpage.png', __('Execute'));
        $titles['NoExecute']  = PMA_Util::getIcon('bd_nextpage.png', __('Execute'));
        $titles['Favorite']   = PMA_Util::getIcon('b_favorite.png', '');
        $titles['NoFavorite'] = PMA_Util::getIcon('b_no_favorite.png', '');

        $this->assertEquals($titles, PMA_Util::buildActionTitles());

    }
}
