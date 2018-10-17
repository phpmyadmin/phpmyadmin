<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

/**
 * Class SqlForm
 * @package PhpMyAdmin\Config\Forms\Setup
 */
class SqlForm extends \PhpMyAdmin\Config\Forms\User\SqlForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        $result = parent::getForms();
        /* Following are not available to user */
        $result['Sql_queries'][] = 'QueryHistoryDB';
        return $result;
    }
}
