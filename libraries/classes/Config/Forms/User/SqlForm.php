<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

class SqlForm extends BaseForm
{
    public static function getForms()
    {
        return array(
            'Sql_queries' => array(
                'ShowSQL',
                'Confirm',
                'QueryHistoryMax',
                'IgnoreMultiSubmitErrors',
                'MaxCharactersInDisplayedSQL',
                'RetainQueryBox',
                'CodemirrorEnable',
                'LintEnable',
                'EnableAutocompleteForTablesAndColumns',
                'DefaultForeignKeyChecks',
            ),
            'Sql_box' => array(
                'SQLQuery/Edit',
                'SQLQuery/Explain',
                'SQLQuery/ShowAsPHP',
                'SQLQuery/Refresh',
            ),
        );
    }

    public static function getName()
    {
        return __('SQL queries');
    }
}
