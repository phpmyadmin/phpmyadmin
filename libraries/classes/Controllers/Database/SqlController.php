<?php
/**
 * Holds the PhpMyAdmin\Controllers\Database\SqlController
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Url;

/**
 * Database SQL executor
 * @package PhpMyAdmin\Controllers\Database
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
        global $goto, $back;

        PageSettings::showGroup('Sql');

        require ROOT_PATH . 'libraries/db_common.inc.php';

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = Url::getFromRoute('/database/sql');
        $back = $goto;

        return $sqlQueryForm->getHtml(
            true,
            false,
            isset($params['delimiter'])
                ? htmlspecialchars($params['delimiter'])
                : ';'
        );
    }
}
