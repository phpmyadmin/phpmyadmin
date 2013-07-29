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
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

$ServerStatusData = new PMA_ServerStatusData();

/**
 * Kills a selected process
 */
if (! empty($_REQUEST['kill'])) {
    if ($GLOBALS['dbi']->tryQuery('KILL ' . $_REQUEST['kill'] . ';')) {
        $message = PMA_Message::success(__('Thread %s was successfully killed.'));
    } else {
        $message = PMA_Message::error(
            __(
                'phpMyAdmin was unable to kill thread %s.'
                . ' It probably has already been closed.'
            )
        );
    }
    $message->addParam($_REQUEST['kill']);
}

/**
 * start output
 */
$response = PMA_Response::getInstance();
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(PMA_getHtmlForServerStatus($ServerStatusData));
$response->addHTML('</div>');

exit;
?>
