<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_buildActionTitles from common.lib
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_buildActionTitles_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_buildActionTitles_test extends PHPUnit_Framework_TestCase{

    function setup()
    {
        $GLOBALS['cfg'] = array('PropertiesIconic' => 'both');
        $GLOBALS['pmaThemeImage'] = 'theme/';
    }

    function testBuildActionTitles(){
        $titles = array();

        $titles['Browse']     = PMA_getIcon('b_browse.png', __('Browse'), true);
        $titles['NoBrowse']   = PMA_getIcon('bd_browse.png', __('Browse'), true);
        $titles['Search']     = PMA_getIcon('b_select.png', __('Search'), true);
        $titles['NoSearch']   = PMA_getIcon('bd_select.png', __('Search'), true);
        $titles['Insert']     = PMA_getIcon('b_insrow.png', __('Insert'), true);
        $titles['NoInsert']   = PMA_getIcon('bd_insrow.png', __('Insert'), true);
        $titles['Structure']  = PMA_getIcon('b_props.png', __('Structure'), true);
        $titles['Drop']       = PMA_getIcon('b_drop.png', __('Drop'), true);
        $titles['NoDrop']     = PMA_getIcon('bd_drop.png', __('Drop'), true);
        $titles['Empty']      = PMA_getIcon('b_empty.png', __('Empty'), true);
        $titles['NoEmpty']    = PMA_getIcon('bd_empty.png', __('Empty'), true);
        $titles['Edit']       = PMA_getIcon('b_edit.png', __('Edit'), true);
        $titles['NoEdit']     = PMA_getIcon('bd_edit.png', __('Edit'), true);
        $titles['Export']     = PMA_getIcon('b_export.png', __('Export'), true);
        $titles['NoExport']   = PMA_getIcon('bd_export.png', __('Export'), true);
        $titles['Execute']    = PMA_getIcon('b_nextpage.png', __('Execute'), true);
        $titles['NoExecute']  = PMA_getIcon('bd_nextpage.png', __('Execute'), true);

        $this->assertEquals($titles, PMA_buildActionTitles());

    }
}
