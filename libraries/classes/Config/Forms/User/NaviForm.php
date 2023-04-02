<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

use function __;

class NaviForm extends BaseForm
{
    /** @return mixed[] */
    public static function getForms(): array
    {
        return [
            'Navi_panel' => [
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
            ],
            'Navi_tree' => [
                'MaxNavigationItems',
                'NavigationTreeEnableGrouping',
                'NavigationTreeEnableExpansion',
                'NavigationTreeShowTables',
                'NavigationTreeShowViews',
                'NavigationTreeShowFunctions',
                'NavigationTreeShowProcedures',
                'NavigationTreeShowEvents',
                'NavigationTreeAutoexpandSingleDb',
            ],
            'Navi_servers' => ['NavigationDisplayServers', 'DisplayServersList'],
            'Navi_databases' => ['NavigationTreeDisplayDbFilterMinimum', 'NavigationTreeDbSeparator'],
            'Navi_tables' => [
                'NavigationTreeDefaultTabTable',
                'NavigationTreeDefaultTabTable2',
                'NavigationTreeTableSeparator',
                'NavigationTreeTableLevel',
            ],
        ];
    }

    public static function getName(): string
    {
        return __('Navigation panel');
    }
}
