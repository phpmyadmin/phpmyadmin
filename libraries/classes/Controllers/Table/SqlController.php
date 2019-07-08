<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\SqlController
 *
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\SqlQueryForm;

/**
 * Table SQL executor
 * @package PhpMyAdmin\Controllers\Table
 */
class SqlController extends AbstractController
{
    /**
     * @param array        $params       Request parameters
     * @param SqlQueryForm $sqlQueryForm SqlQueryForm instance
     *
     * @return string HTML
     */
    public function index(array $params, SqlQueryForm $sqlQueryForm): string
    {
        global $url_query, $err_url, $goto, $back;

        PageSettings::showGroup('Sql');

        require ROOT_PATH . 'libraries/tbl_common.inc.php';

        $url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';
        $err_url = 'tbl_sql.php' . $err_url;

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = 'tbl_sql.php';
        $back = 'tbl_sql.php';

        return $sqlQueryForm->getHtml(
            $params['sql_query'] ?? true,
            false,
            isset($params['delimiter'])
                ? htmlspecialchars($params['delimiter'])
                : ';'
        );
    }
}
