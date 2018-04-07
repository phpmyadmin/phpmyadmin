<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

class NaviForm extends BaseForm
{
    public static function getForms()
    {
        return array(
            'Navi_panel' => array(
                'ShowDatabasesNavigationAsTree',
                'NavigationLinkWithMainPanel',
                'NavigationDisplayLogo',
                'NavigationLogoLink',
                'NavigationLogoLinkWindow',
                'NavigationTreePointerEnable',
                'FirstLevelNavigationItems',
                'NavigationTreeDisplayItemFilterMinimum',
                'NumRecentTables',
                'NumFavoriteTables',
                'NavigationWidth',
            ),
            'Navi_tree' => array(
                'MaxNavigationItems',
                'NavigationTreeEnableGrouping',
                'NavigationTreeEnableExpansion',
                'NavigationTreeShowTables',
                'NavigationTreeShowViews',
                'NavigationTreeShowFunctions',
                'NavigationTreeShowProcedures',
                'NavigationTreeShowEvents'
            ),
            'Navi_servers' => array(
                'NavigationDisplayServers',
                'DisplayServersList',
            ),
            'Navi_databases' => array(
                'NavigationTreeDisplayDbFilterMinimum',
                'NavigationTreeDbSeparator'
            ),
            'Navi_tables' => array(
                'NavigationTreeDefaultTabTable',
                'NavigationTreeDefaultTabTable2',
                'NavigationTreeTableSeparator',
                'NavigationTreeTableLevel',
            ),
        );
    }

    public static function getName()
    {
        return __('Navigation panel');
    }
}
