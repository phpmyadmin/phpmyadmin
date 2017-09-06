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
        return array(
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
            ),
            'Databases' => array(
                'Servers/1/only_db', // saves to Server/only_db
                'Servers/1/hide_db', // saves to Server/hide_db
                'MaxDbList',
                'MaxTableList',
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
            // settings from this form are treated specially,
            // see prefs_forms.php and user_preferences.lib.php
            'Developer' => array(
                'DBG/sql'
            ),
        );
    }
}
