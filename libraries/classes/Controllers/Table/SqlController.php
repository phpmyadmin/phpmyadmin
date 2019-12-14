<?php
/**
 * Holds the PhpMyAdmin\Controllers\Table\SqlController
 *
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Url;

/**
 * Table SQL executor
 *
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

        $err_url = Url::getFromRoute('/table/sql') . $err_url;

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = Url::getFromRoute('/table/sql');
        $back = Url::getFromRoute('/table/sql');
        $url_query .= Url::getCommon([
            'goto' => $goto,
            'back' => $back,
        ], '&');

        return $sqlQueryForm->getHtml(
            $params['sql_query'] ?? true,
            false,
            isset($params['delimiter'])
                ? htmlspecialchars($params['delimiter'])
                : ';'
        );
    }
}
