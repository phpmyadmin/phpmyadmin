<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

use function __;

class SqlForm extends BaseForm
{
    /** @return mixed[] */
    public static function getForms(): array
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
            'Sql_box' => ['SQLQuery/Edit', 'SQLQuery/Explain', 'SQLQuery/ShowAsPHP', 'SQLQuery/Refresh'],
        ];
    }

    public static function getName(): string
    {
        return __('SQL queries');
    }
}
