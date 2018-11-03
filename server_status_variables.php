<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays a list of server status variables
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Response;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Variables;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/replication.inc.php';

/**
 * flush status variables if requested
 */
if (isset($_POST['flush'])) {
    $_flush_commands = array(
        'STATUS',
        'TABLES',
        'QUERY CACHE',
    );

    if (in_array($_POST['flush'], $_flush_commands)) {
        $GLOBALS['dbi']->query('FLUSH ' . $_POST['flush'] . ';');
    }
    unset($_flush_commands);
}

$serverStatusData = new Data();

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_status_variables.js');
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('server_status_sorter.js');

$response->addHTML('<div>');
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(Variables::getHtmlForFilter($serverStatusData));
    $response->addHTML(Variables::getHtmlForLinkSuggestions($serverStatusData));
    $response->addHTML(Variables::getHtmlForVariablesList($serverStatusData));
} else {
    $response->addHTML(
        Message::error(
            __('Not enough privilege to view status variables.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');

exit;
