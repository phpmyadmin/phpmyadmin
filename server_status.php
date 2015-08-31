<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server status page: processes, connections and traffic
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status.lib.php';

/**
 * Replication library
 */
if (PMA_DRIZZLE) {
    $GLOBALS['replication_info'] = array();
    $GLOBALS['replication_info']['master']['status'] = false;
    $GLOBALS['replication_info']['slave']['status'] = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

/**
 * start output
 */
$response = PMA_Response::getInstance();
$response->addHTML('<div>');

$serverStatusData = new PMA_ServerStatusData();
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(PMA_getHtmlForServerStatus($serverStatusData));
} else {
    $response->addHTML(
        PMA_Message::error(
            __('Not enough privilege to view server status.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');
exit;
