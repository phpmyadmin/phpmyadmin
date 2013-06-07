<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Shared code for server pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Handles some variables that may have been sent by the calling script
 * Note: this can be called also from the db panel to get the privileges of
 *       a db, in which case we want to keep displaying the tabs of
 *       the Database panel
 */
if (empty($viewing_mode)) {
    $db = $table = '';
}

/**
 * Set parameters for links
 */
$url_query = PMA_generate_common_url($db);

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url = 'index.php' . $url_query;

/**
 * @global boolean Checks for superuser privileges
 */
$is_superuser = $GLOBALS['dbi']->isSuperuser();

// now, select the mysql db
if ($is_superuser && ! PMA_DRIZZLE) {
    $GLOBALS['dbi']->selectDb('mysql', $userlink);
}

/**
 * @global array binary log files
 */
$binary_logs = PMA_DRIZZLE
    ? null
    : $GLOBALS['dbi']->fetchResult(
        'SHOW MASTER LOGS',
        'Log_name',
        null,
        null,
        PMA_DatabaseInterface::QUERY_STORE
    );

PMA_Util::checkParameters(
    array('is_superuser', 'url_query'), false
);

/**
 * Returns the html for the sub-page heading
 *
 * @param string $type Sub page type
 *
 * @return string
 */
function PMA_getSubPageHeader($type)
{
    $res = array();

    $res['plugins']['icon'] = 'b_engine.png';
    $res['plugins']['text'] = __('Plugins');
    
    $res['binlog']['icon'] = 's_tbl.png';
    $res['binlog']['text'] = __('Binary log');
    
    $html = '<h2>' . "\n"
        . PMA_Util::getImage($res[$type]['icon'])
        . '    ' . $res[$type]['text'] . "\n"
        . '</h2>' . "\n";
    return $html;
}
?>
