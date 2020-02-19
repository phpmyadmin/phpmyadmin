<?php
/**
 * Ensure the database and the table exist (else move to the "parent" script)
 * and display headers
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\SqlController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder, $db, $table, $dbi, $is_db, $is_table, $message, $show_as_php, $sql_query;

if (empty($is_db)) {
    if (strlen($db) > 0) {
        $is_db = @$dbi->selectDb($db);
    } else {
        $is_db = false;
    }

    if (! $is_db) {
        // not a valid db name -> back to the welcome page
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            $response = Response::getInstance();
            if ($response->isAjax()) {
                $response->setRequestStatus(false);
                $response->addJSON(
                    'message',
                    Message::error(__('No databases selected.'))
                );
            } else {
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
                Core::sendHeaderLocation(
                    './index.php?route=/'
                    . Url::getCommonRaw($url_params, '&')
                );
            }
            exit;
        }
    }
}

if (empty($is_table)
    && ! defined('PMA_SUBMIT_MULT')
    && ! defined('TABLE_MAY_BE_ABSENT')
) {
    // Not a valid table name -> back to the /database/sql

    if (strlen($table) > 0) {
        $is_table = $dbi->getCachedTableContent([$db, $table], false);

        if (! $is_table) {
            $_result = $dbi->tryQuery(
                'SHOW TABLES LIKE \''
                . $dbi->escapeString($table) . '\';',
                PhpMyAdmin\DatabaseInterface::CONNECT_USER,
                PhpMyAdmin\DatabaseInterface::QUERY_STORE
            );
            $is_table = @$dbi->numRows($_result);
            $dbi->freeResult($_result);
        }
    } else {
        $is_table = false;
    }

    if (! $is_table) {
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            if (strlen($table) > 0) {
                // SHOW TABLES doesn't show temporary tables, so try select
                // (as it can happen just in case temporary table, it should be
                // fast):

                /**
                 * @todo should this check really
                 * only happen if IS_TRANSFORMATION_WRAPPER?
                 */
                $_result = $dbi->tryQuery(
                    'SELECT COUNT(*) FROM ' . PhpMyAdmin\Util::backquote($table)
                    . ';',
                    PhpMyAdmin\DatabaseInterface::CONNECT_USER,
                    PhpMyAdmin\DatabaseInterface::QUERY_STORE
                );
                $is_table = ($_result && @$dbi->numRows($_result));
                $dbi->freeResult($_result);
            }

            if (! $is_table) {
                /** @var SqlController $controller */
                $controller = $containerBuilder->get(SqlController::class);
                $controller->index();
                exit;
            }
        }

        if (! $is_table) {
            exit;
        }
    }
}
