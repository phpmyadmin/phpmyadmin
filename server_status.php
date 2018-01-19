<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server status page: processes, connections and traffic
 *
 * @package PhpMyAdmin
 */

use PMA\libraries\Message;
use PMA\libraries\Response;
use PMA\libraries\ServerStatusData;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/server_status.lib.php';

/**
 * Replication library
 */
require_once 'libraries/replication.inc.php';
require_once 'libraries/replication_gui.lib.php';

/**
 * start output
 */
$response = Response::getInstance();
$response->addHTML('<div>');

$serverStatusData = new ServerStatusData();
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(PMA_getHtmlForServerStatus($serverStatusData));
} else {
    $response->addHTML(
        Message::error(
            __('Not enough privilege to view server status.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');
exit;
