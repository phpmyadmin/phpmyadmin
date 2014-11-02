<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Ensure the database and the table exist (else move to the "parent" script)
 * and display headers
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/** @var PMA_String $pmaString */
$pmaString = $GLOBALS['PMA_String'];
if (empty($is_db)) {
    if (/*overload*/mb_strlen($db)) {
        $is_db = @$GLOBALS['dbi']->selectDb($db);
    } else {
        $is_db = false;
    }

    if (! $is_db) {
        // not a valid db name -> back to the welcome page
        if (! defined('IS_TRANSFORMATION_WRAPPER')) {
            $response = PMA_Response::getInstance();
            if ($response->isAjax()) {
                $response->isSuccess(false);
                $response->addJSON(
                    'message',
                    PMA_Message::error(__('No databases selected.'))
                );
            } else {
                $url_params = array('reload' => 1);
                if (isset($message)) {
                    $url_params['message'] = $message;
                }
                if (! empty($sql_query)) {
                    $url_params['sql_query'] = $sql_query;
                }
                if (isset($show_as_php)) {
                    $url_params['show_as_php'] = $show_as_php;
                }
                PMA_sendHeaderLocation(
                    $cfg['PmaAbsoluteUri'] . 'index.php'
                    . PMA_URL_getCommon($url_params, 'text')
                );
            }
            exit;
        }
    }
} // end if (ensures db exists)

if (empty($is_table)
    && !defined('PMA_SUBMIT_MULT')
    && !defined('TABLE_MAY_BE_ABSENT')
) {
    // Not a valid table name -> back to the db_sql.php

    if (/*overload*/mb_strlen($table)) {
        $is_table = isset(PMA_Table::$cache[$db][$table]);

        if (! $is_table) {
            $_result = $GLOBALS['dbi']->tryQuery(
                'SHOW TABLES LIKE \'' . PMA_Util::sqlAddSlashes($table, true)
                . '\';',
                null, PMA_DatabaseInterface::QUERY_STORE
            );
            $is_table = @$GLOBALS['dbi']->numRows($_result);
            $GLOBALS['dbi']->freeResult($_result);
        }
    } else {
        $is_table = false;
    }

    if (! $is_table) {
        if (!defined('IS_TRANSFORMATION_WRAPPER')) {
            if (/*overload*/mb_strlen($table)) {
                // SHOW TABLES doesn't show temporary tables, so try select
                // (as it can happen just in case temporary table, it should be
                // fast):

                /**
                 * @todo should this check really
                 * only happen if IS_TRANSFORMATION_WRAPPER?
                 */
                $_result = $GLOBALS['dbi']->tryQuery(
                    'SELECT COUNT(*) FROM ' . PMA_Util::backquote($table) . ';',
                    null,
                    PMA_DatabaseInterface::QUERY_STORE
                );
                $is_table = ($_result && @$GLOBALS['dbi']->numRows($_result));
                $GLOBALS['dbi']->freeResult($_result);
            }

            if (! $is_table) {
                include './db_sql.php';
                exit;
            }
        }

        if (! $is_table) {
            exit;
        }
    }
} // end if (ensures table exists)
?>
