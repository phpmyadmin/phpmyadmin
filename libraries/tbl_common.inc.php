<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common includes for the table level views
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

// Check parameters
PMA_Util::checkParameters(array('db', 'table'));

$db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($db);

/**
 * Set parameters for links
 * @deprecated
 */
$url_query = PMA_URL_getCommon($db, $table);

/**
 * Set parameters for links
 */
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = $cfg['DefaultTabDatabase']
    . PMA_URL_getCommon(array('db' => $db,));
$err_url   = $cfg['DefaultTabTable'] . PMA_URL_getCommon($url_params);


/**
 * Ensures the database and the table exist (else move to the "parent" script)
 */
require_once './libraries/db_table_exists.lib.php';

if (PMA_Tracker::isActive()
    && PMA_Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"])
    && ! isset($_REQUEST['submit_deactivate_now'])
    && ! (isset($_REQUEST['report_export'])
    && $_REQUEST['export_type'] == 'sqldumpfile')
) {
    $temp_msg = '<a href="tbl_tracking.php?' . $url_query . '">';
    $temp_msg .= sprintf(
        __('Tracking of %s is activated.'),
        htmlspecialchars($GLOBALS["db"] . '.' . $GLOBALS["table"])
    );
    $temp_msg .= '</a>';

    $msg = PMA_Message::notice($temp_msg);
    PMA_Response::getInstance()->addHTML($msg->getDisplay());
}

?>
