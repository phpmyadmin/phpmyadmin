<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Core;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Mime;
use PhpMyAdmin\Util;
use function ini_set;

/**
 * Provides download to a given field defined in parameters.
 *
 * @package PhpMyAdmin\Controllers\Table
 */
class GetFieldController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        global $db, $table;

        $this->response->disable();

        /* Check parameters */
        Util::checkParameters([
            'db',
            'table',
        ]);

        /* Select database */
        if (! $this->dbi->selectDb($db)) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
                '',
                false
            );
        }

        /* Check if table exists */
        if (! $this->dbi->getColumns($db, $table)) {
            Generator::mysqlDie(__('Invalid table name'));
        }

        /* Grab data */
        $sql = 'SELECT ' . Util::backquote($_GET['transform_key'])
            . ' FROM ' . Util::backquote($table)
            . ' WHERE ' . $_GET['where_clause'] . ';';
        $result = $this->dbi->fetchValue($sql);

        /* Check return code */
        if ($result === false) {
            Generator::mysqlDie(
                __('MySQL returned an empty result set (i.e. zero rows).'),
                $sql
            );
        }

        /* Avoid corrupting data */
        ini_set('url_rewriter.tags', '');

        Core::downloadHeader(
            $table . '-' . $_GET['transform_key'] . '.bin',
            Mime::detect($result),
            strlen($result)
        );
        echo $result;
    }
}
