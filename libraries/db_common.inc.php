<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common includes for the database level views
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Gets some core libraries
 */
require_once './libraries/bookmark.lib.php';

PMA_Util::checkParameters(array('db'));

global $cfg;
global $db;

$is_show_stats = $cfg['ShowStats'];

$db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($db);
if ($db_is_system_schema) {
    $is_show_stats = false;
}

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = 'index.php' . PMA_URL_getCommon();

$err_url = PMA_Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
)
    . PMA_URL_getCommon(array('db' => $db));

/** @var PMA_String $pmaString */
$pmaString = $GLOBALS['PMA_String'];

/**
 * Ensures the database exists (else move to the "parent" script) and displays
 * headers
 */
if (! isset($is_db) || ! $is_db) {
    if (/*overload*/mb_strlen($db)) {
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
    $uri = $cfg['PmaAbsoluteUri'] . 'index.php'
        . PMA_URL_getCommon(array(), 'text')
        . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1';
    if (!/*overload*/mb_strlen($db) || ! $is_db) {
        $response = PMA_Response::getInstance();
        if ($response->isAjax()) {
            $response->isSuccess(false);
            $response->addJSON(
                'message',
                PMA_Message::error(__('No databases selected.'))
            );
        } else {
            PMA_sendHeaderLocation($uri);
        }
        exit;
    }
} // end if (ensures db exists)

/**
 * Changes database charset if requested by the user
 */
if (isset($_REQUEST['submitcollation'])
    && isset($_REQUEST['db_collation'])
    && ! empty($_REQUEST['db_collation'])
) {
    list($db_charset) = explode('_', $_REQUEST['db_collation']);
    $sql_query        = 'ALTER DATABASE '
        . PMA_Util::backquote($db)
        . ' DEFAULT' . PMA_generateCharsetQueryPart($_REQUEST['db_collation']);
    $result           = $GLOBALS['dbi']->query($sql_query);
    $message          = PMA_Message::success();
    unset($db_charset);

    /**
     * If we are in an Ajax request, let us stop the execution here. Necessary for
     * db charset change action on db_operations.php.  If this causes a bug on
     * other pages, we might have to move this to a different location.
     */
    if ($GLOBALS['is_ajax_request'] == true) {
        $response = PMA_Response::getInstance();
        $response->isSuccess($message->isSuccess());
        $response->addJSON('message', $message);
        exit;
    }
}

/**
 * Set parameters for links
 */
$url_query = PMA_URL_getCommon(array('db' => $db));

