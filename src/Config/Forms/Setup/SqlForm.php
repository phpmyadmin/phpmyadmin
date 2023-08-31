<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

class SqlForm extends \PhpMyAdmin\Config\Forms\User\SqlForm
{
    /** @return mixed[] */
    public static function getForms(): array
    {
        $result = parent::getForms();
        /* Following are not available to user */
        $result['Sql_queries'][] = 'QueryHistoryDB';
        $result['Sql_queries'][] = 'AllowSharedBookmarks';

        return $result;
    }
}
