<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common includes for the database level views
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

Util::checkParameters(['db']);

global $cfg;
global $db;

$response = Response::getInstance();
$is_show_stats = $cfg['ShowStats'];

$db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($db);
if ($db_is_system_schema) {
    $is_show_stats = false;
}

$relation = new Relation($GLOBALS['dbi']);
$operations = new Operations($GLOBALS['dbi'], $relation);

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = 'index.php' . Url::getCommon();

$err_url = Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabDatabase'],
    'database'
)
    . Url::getCommon(['db' => $db]);

/**
 * Ensures the database exists (else move to the "parent" script) and displays
 * headers
 */
if (! isset($is_db) || ! $is_db) {
    if (strlen($db) > 0) {
        $is_db = $GLOBALS['dbi']->selectDb($db);
        // This "Command out of sync" 2014 error may happen, for example
        // after calling a MySQL procedure; at this point we can't select
        // the db but it's not necessarily wrong
        if ($GLOBALS['dbi']->getError() && $GLOBALS['errno'] == 2014) {
            $is_db = true;
            unset($GLOBALS['errno']);
        }
    } else {
        $is_db = false;
    }
    // Not a valid db name -> back to the welcome page
    $params = ['reload' => '1'];
    if (isset($message)) {
        $params['message'] = $message;
    }
    $uri = './index.php' . Url::getCommonRaw($params);
    if (strlen($db) === 0 || ! $is_db) {
        $response = Response::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            $response->addJSON(
                'message',
                Message::error(__('No databases selected.'))
            );
        } else {
            Core::sendHeaderLocation($uri);
        }
        exit;
    }
} // end if (ensures db exists)

/**
 * Changes database charset if requested by the user
 */
if (isset($_POST['submitcollation'])
    && isset($_POST['db_collation'])
    && ! empty($_POST['db_collation'])
) {
    list($db_charset) = explode('_', $_POST['db_collation']);
    $sql_query        = 'ALTER DATABASE '
        . Util::backquote($db)
        . ' DEFAULT' . Util::getCharsetQueryPart($_POST['db_collation']);
    $result           = $GLOBALS['dbi']->query($sql_query);
    $message          = Message::success();

    /**
     * Changes tables charset if requested by the user
     */
    if (isset($_POST['change_all_tables_collations']) &&
        $_POST['change_all_tables_collations'] === 'on'
    ) {
        list($tables, , , , , , , ,) = Util::getDbInfo($db, null);
        foreach ($tables as $tableName => $data) {
            if ($GLOBALS['dbi']->getTable($db, $tableName)->isView()) {
                // Skip views, we can not change the collation of a view.
                // issue #15283
                continue;
            }
            $sql_query      = 'ALTER TABLE '
            . Util::backquote($db)
            . '.'
            . Util::backquote($tableName)
            . ' DEFAULT '
            . Util::getCharsetQueryPart($_POST['db_collation']);
            $GLOBALS['dbi']->query($sql_query);

            /**
             * Changes columns charset if requested by the user
             */
            if (isset($_POST['change_all_tables_columns_collations']) &&
                $_POST['change_all_tables_columns_collations'] === 'on'
            ) {
                $operations->changeAllColumnsCollation($db, $tableName, $_POST['db_collation']);
            }
        }
    }
    unset($db_charset);

    /**
     * If we are in an Ajax request, let us stop the execution here. Necessary for
     * db charset change action on db_operations.php.  If this causes a bug on
     * other pages, we might have to move this to a different location.
     */
    if ($response->isAjax()) {
        $response->setRequestStatus($message->isSuccess());
        $response->addJSON('message', $message);
        exit;
    }
} elseif (isset($_POST['submitcollation'])
    && isset($_POST['db_collation'])
    && empty($_POST['db_collation'])
) {
    $response = Response::getInstance();
    if ($response->isAjax()) {
        $response->setRequestStatus(false);
        $response->addJSON(
            'message',
            Message::error(__('No collation provided.'))
        );
    }
}

/**
 * Set parameters for links
 */
$url_query = Url::getCommon(['db' => $db]);
