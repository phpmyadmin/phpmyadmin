<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

/**
 * Class FeaturesForm
 * @package PhpMyAdmin\Config\Forms\User
 */
class FeaturesForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        $result = [
            'General' => [
                'VersionCheck',
                'NaturalOrder',
                'InitialSlidersState',
                'SkipLockedTables',
                'DisableMultiTableMaintenance',
                'ShowHint',
                'SendErrorReports',
                'ConsoleEnterExecutes',
                'DisableShortcutKeys',
            ],
            'Databases' => [
                'Servers/1/only_db', // saves to Server/only_db
                'Servers/1/hide_db', // saves to Server/hide_db
                'MaxDbList',
                'MaxTableList',
                'DefaultConnectionCollation',
            ],
            'Text_fields' => [
                'CharEditing',
                'MinSizeForInputField',
                'MaxSizeForInputField',
                'CharTextareaCols',
                'CharTextareaRows',
                'TextareaCols',
                'TextareaRows',
                'LongtextDoubleTextarea',
            ],
            'Page_titles' => [
                'TitleDefault',
                'TitleTable',
                'TitleDatabase',
                'TitleServer',
            ],
            'Warnings' => [
                'PmaNoRelation_DisableWarning',
                'SuhosinDisableWarning',
                'LoginCookieValidityDisableWarning',
                'ReservedWordDisableWarning',
            ],
            'Console' => [
                'Console/Mode',
                'Console/StartHistory',
                'Console/AlwaysExpand',
                'Console/CurrentQuery',
                'Console/EnterExecutes',
                'Console/DarkTheme',
                'Console/Height',
                'Console/GroupQueries',
                'Console/OrderBy',
                'Console/Order',
            ],
        ];
        // skip Developer form if no setting is available
        if ($GLOBALS['cfg']['UserprefsDeveloperTab']) {
            $result['Developer'] = [
                'DBG/sql',
            ];
        }
        return $result;
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return __('Features');
    }
}
