<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the advisor feature
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/Advisor.class.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status_advisor.lib.php';

if (PMA_DRIZZLE) {
    $GLOBALS['replication_info'] = array();
    $GLOBALS['replication_info']['master']['status'] = false;
    $GLOBALS['replication_info']['slave']['status'] = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

$serverStatusData = new PMA_ServerStatusData();

$response = PMA_Response::getInstance();
$scripts = $response->getHeader()->getScripts();
$scripts->addFile('server_status_advisor.js');

/**
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(PMA_getHtmlForAdvisor());
} else {
    $response->addHTML(
        PMA_Message::error(
            __('Not enough privilege to view the advisor.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');

exit;
