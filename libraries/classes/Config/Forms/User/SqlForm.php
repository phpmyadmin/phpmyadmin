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
 * Class SqlForm
 * @package PhpMyAdmin\Config\Forms\User
 */
class SqlForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Sql_queries' => [
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
            ],
            'Sql_box' => [
                'SQLQuery/Edit',
                'SQLQuery/Explain',
                'SQLQuery/ShowAsPHP',
                'SQLQuery/Refresh',
            ],
        ];
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return __('SQL queries');
    }
}
