<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays a list of server status variables
 *
 * @package PhpMyAdmin
 */

use PMA\libraries\Response;
use PMA\libraries\Message;
use PMA\libraries\ServerStatusData;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/server_status_variables.lib.php';
require_once 'libraries/replication.inc.php';
require_once 'libraries/replication_gui.lib.php';

/**
 * flush status variables if requested
 */
if (isset($_REQUEST['flush'])) {
    $_flush_commands = array(
        'STATUS',
        'TABLES',
        'QUERY CACHE',
    );

    if (in_array($_REQUEST['flush'], $_flush_commands)) {
        $GLOBALS['dbi']->query('FLUSH ' . $_REQUEST['flush'] . ';');
    }
    unset($_flush_commands);
}

$serverStatusData = new ServerStatusData();

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_status_variables.js');
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_status_sorter.js');

$response->addHTML('<div>');
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(PMA_getHtmlForFilter($serverStatusData));
    $response->addHTML(PMA_getHtmlForLinkSuggestions($serverStatusData));
    $response->addHTML(PMA_getHtmlForVariablesList($serverStatusData));
} else {
    $response->addHTML(
        Message::error(
            __('Not enough privilege to view status variables.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');

exit;
