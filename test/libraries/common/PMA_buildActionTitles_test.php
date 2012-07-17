<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_CommonFunctions::buildActionTitles from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_buildActionTitles_test extends PHPUnit_Framework_TestCase
{

    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $GLOBALS['cfg'] = array('PropertiesIconic' => 'both');
        $GLOBALS['pmaThemeImage'] = 'theme/';
    }

    function testBuildActionTitles()
    {
        $titles = array();
        $common = PMA_CommonFunctions::getInstance();

        $titles['Browse']     = $common->getIcon('b_browse.png', __('Browse'));
        $titles['NoBrowse']   = $common->getIcon('bd_browse.png', __('Browse'));
        $titles['Search']     = $common->getIcon('b_select.png', __('Search'));
        $titles['NoSearch']   = $common->getIcon('bd_select.png', __('Search'));
        $titles['Insert']     = $common->getIcon('b_insrow.png', __('Insert'));
        $titles['NoInsert']   = $common->getIcon('bd_insrow.png', __('Insert'));
        $titles['Structure']  = $common->getIcon('b_props.png', __('Structure'));
        $titles['Drop']       = $common->getIcon('b_drop.png', __('Drop'));
        $titles['NoDrop']     = $common->getIcon('bd_drop.png', __('Drop'));
        $titles['Empty']      = $common->getIcon('b_empty.png', __('Empty'));
        $titles['NoEmpty']    = $common->getIcon('bd_empty.png', __('Empty'));
        $titles['Edit']       = $common->getIcon('b_edit.png', __('Edit'));
        $titles['NoEdit']     = $common->getIcon('bd_edit.png', __('Edit'));
        $titles['Export']     = $common->getIcon('b_export.png', __('Export'));
        $titles['NoExport']   = $common->getIcon('bd_export.png', __('Export'));
        $titles['Execute']    = $common->getIcon('b_nextpage.png', __('Execute'));
        $titles['NoExecute']  = $common->getIcon('bd_nextpage.png', __('Execute'));

        $this->assertEquals($titles, $common->buildActionTitles());

    }
}
