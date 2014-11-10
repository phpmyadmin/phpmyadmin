<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the server status > processes list
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status_processes.lib.php';

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

$ServerStatusData = new PMA_ServerStatusData();
$response = PMA_Response::getInstance();

/**
 * Kills a selected process
 * on ajax request
 */
if ($response->isAjax() && !empty($_REQUEST['kill'])) {
    $query = $GLOBALS['dbi']->getKillQuery((int)$_REQUEST['kill']);
    if ($GLOBALS['dbi']->tryQuery($query)) {
        $message = PMA_Message::success(__('Thread %s was successfully killed.'));
        $response->isSuccess(true);
    } else {
        $message = PMA_Message::error(
            __(
                'phpMyAdmin was unable to kill thread %s.'
                . ' It probably has already been closed.'
            )
        );
        $response->isSuccess(false);
    }
    $message->addParam($_REQUEST['kill']);
    $response->addJSON('message', $message);
} elseif ($response->isAjax() && !empty($_REQUEST['refresh'])) {
    // Only sends the process list table
    $response->addHTML(PMA_getHtmlForServerProcessList());
} else {
    // Load the full page
    $header   = $response->getHeader();
    $scripts  = $header->getScripts();
    $scripts->addFile('server_status_processes.js');
    $response->addHTML('<div>');
    $response->addHTML($ServerStatusData->getMenuHtml());
    $response->addHTML(PMA_getHtmlForServerProcesses());
    $response->addHTML('</div>');
}
exit;
?>
