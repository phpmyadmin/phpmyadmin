<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the server status > processes list
 *
 * @package PhpMyAdmin
 */

use PMA\libraries\Response;
use PMA\libraries\ServerStatusData;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/server_status_processes.lib.php';

/**
 * Replication library
 */
require_once 'libraries/replication.inc.php';
require_once 'libraries/replication_gui.lib.php';

$ServerStatusData = new ServerStatusData();
$response = Response::getInstance();

/**
 * Kills a selected process
 * on ajax request
 */
if ($response->isAjax() && !empty($_REQUEST['kill'])) {
    $kill = intval($_REQUEST['kill']);
    $query = $GLOBALS['dbi']->getKillQuery($kill);
    if ($GLOBALS['dbi']->tryQuery($query)) {
        $message = PMA\libraries\Message::success(
            __('Thread %s was successfully killed.')
        );
        $response->setRequestStatus(true);
    } else {
        $message = PMA\libraries\Message::error(
            __(
                'phpMyAdmin was unable to kill thread %s.'
                . ' It probably has already been closed.'
            )
        );
        $response->setRequestStatus(false);
    }
    $message->addParam($kill);
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
    $response->addHTML(PMA_getHtmlForProcessListFilter());
    $response->addHTML(PMA_getHtmlForServerProcesslist());
    $response->addHTML(PMA_getHtmlForProcessListAutoRefresh());
    $response->addHTML('</div>');
}
exit;
