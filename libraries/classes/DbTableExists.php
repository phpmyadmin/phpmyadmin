<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Controllers\Database\SqlController;
use function defined;
use function strlen;

final class DbTableExists
{
    /**
     * Ensure the database and the table exist (else move to the "parent" script)
     * and display headers
     */
    public static function check(): void
    {
        self::checkDatabase();
        self::checkTable();
    }

    private static function checkDatabase(): void
    {
        global $db, $dbi, $is_db, $message, $show_as_php, $sql_query;

        if (! empty($is_db)) {
            return;
        }

        $is_db = false;
        if (strlen($db) > 0) {
            $is_db = @$dbi->selectDb($db);
        }

        if ($is_db || defined('IS_TRANSFORMATION_WRAPPER')) {
            return;
        }

        $response = Response::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON(
                'message',
                Message::error(__('No databases selected.'))
            );

            exit;
        }

        $url_params = ['reload' => 1];

        if (isset($message)) {
            $url_params['message'] = $message;
        }

        if (! empty($sql_query)) {
            $url_params['sql_query'] = $sql_query;
        }

        if (isset($show_as_php)) {
            $url_params['show_as_php'] = $show_as_php;
        }

        Core::sendHeaderLocation('./index.php?route=/' . Url::getCommonRaw($url_params, '&'));

        exit;
    }

    private static function checkTable(): void
    {
        global $containerBuilder, $db, $table, $dbi, $is_table;

        if (! empty($is_table)
            || defined('PMA_SUBMIT_MULT')
            || defined('TABLE_MAY_BE_ABSENT')
        ) {
            return;
        }

        $is_table = false;
        if (strlen($table) > 0) {
            $is_table = $dbi->getCache()->getCachedTableContent([$db, $table], false);
            if ($is_table) {
                return;
            }

            $_result = $dbi->tryQuery(
                'SHOW TABLES LIKE \'' . $dbi->escapeString($table) . '\';',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            $is_table = @$dbi->numRows($_result);
            $dbi->freeResult($_result);
        }

        if ($is_table) {
            return;
        }

        if (defined('IS_TRANSFORMATION_WRAPPER')) {
            exit;
        }

        if (strlen($table) > 0) {
            /**
             * SHOW TABLES doesn't show temporary tables, so try select
             * (as it can happen just in case temporary table, it should be fast):
             */
            $_result = $dbi->tryQuery(
                'SELECT COUNT(*) FROM ' . Util::backquote($table) . ';',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            $is_table = ($_result && @$dbi->numRows($_result));
            $dbi->freeResult($_result);
        }

        if ($is_table) {
            return;
        }

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index();

        exit;
    }
}
