<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

class FeaturesForm extends BaseForm
{
    public static function getForms()
    {
        $result = array(
            'General' => array(
                'VersionCheck',
                'NaturalOrder',
                'InitialSlidersState',
                'SkipLockedTables',
                'DisableMultiTableMaintenance',
                'ShowHint',
                'SendErrorReports',
                'ConsoleEnterExecutes',
                'DisableShortcutKeys',
                'FontSize',
            ),
            'Databases' => array(
                'Servers/1/only_db', // saves to Server/only_db
                'Servers/1/hide_db', // saves to Server/hide_db
                'MaxDbList',
                'MaxTableList',
                'DefaultConnectionCollation',
            ),
            'Text_fields' => array(
                'CharEditing',
                'MinSizeForInputField',
                'MaxSizeForInputField',
                'CharTextareaCols',
                'CharTextareaRows',
                'TextareaCols',
                'TextareaRows',
                'LongtextDoubleTextarea'
            ),
            'Page_titles' => array(
                'TitleDefault',
                'TitleTable',
                'TitleDatabase',
                'TitleServer'
            ),
            'Warnings' => array(
                'PmaNoRelation_DisableWarning',
                'SuhosinDisableWarning',
                'LoginCookieValidityDisableWarning',
                'ReservedWordDisableWarning'
            ),
            'Console' => array(
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
            ),
        );
        // skip Developer form if no setting is available
        if ($GLOBALS['cfg']['UserprefsDeveloperTab']) {
            $result['Developer'] = array(
                'DBG/sql'
            );
        }
        return $result;
    }

    public static function getName()
    {
        return __('Features');
    }
}
