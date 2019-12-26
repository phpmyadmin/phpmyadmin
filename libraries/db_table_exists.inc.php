<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Ensure the database and the table exist (else move to the "parent" script)
 * and display headers
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $db, $table;

if (empty($is_db)) {
    if (strlen($db) > 0) {
        $is_db = @$GLOBALS['dbi']->selectDb($db);
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
                    './index.php'
                    . Url::getCommonRaw($url_params)
                );
            }
            exit;
        }
    }
} // end if (ensures db exists)

if (empty($is_table)
    && ! defined('PMA_SUBMIT_MULT')
    && ! defined('TABLE_MAY_BE_ABSENT')
) {
    // Not a valid table name -> back to the db_sql.php

    if (strlen($table) > 0) {
        $is_table = $GLOBALS['dbi']->getCachedTableContent([$db, $table], false);

        if (! $is_table) {
            $_result = $GLOBALS['dbi']->tryQuery(
                'SHOW TABLES LIKE \''
                . $GLOBALS['dbi']->escapeString($table) . '\';',
                PhpMyAdmin\DatabaseInterface::CONNECT_USER,
                PhpMyAdmin\DatabaseInterface::QUERY_STORE
            );
            $is_table = @$GLOBALS['dbi']->numRows($_result);
            $GLOBALS['dbi']->freeResult($_result);
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
                $_result = $GLOBALS['dbi']->tryQuery(
                    'SELECT COUNT(*) FROM ' . PhpMyAdmin\Util::backquote($table)
                    . ';',
                    PhpMyAdmin\DatabaseInterface::CONNECT_USER,
                    PhpMyAdmin\DatabaseInterface::QUERY_STORE
                );
                $is_table = ($_result && @$GLOBALS['dbi']->numRows($_result));
                $GLOBALS['dbi']->freeResult($_result);
            }

            if (! $is_table) {
                include ROOT_PATH . 'db_sql.php';
                exit;
            }
        }

        if (! $is_table) {
            exit;
        }
    }
} // end if (ensures table exists)
