<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Displays query statistics for the server
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status_queries.lib.php';

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
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_status_queries.js');

/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $scripts->addFile('jqplot/excanvas.js');
}

// for charting
$scripts->addFile('jqplot/jquery.jqplot.js');
$scripts->addFile('jqplot/plugins/jqplot.pieRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('jqplot/plugins/jqplot.cursor.js');
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_status_sorter.js');

// Add the html content to the response
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(PMA_getHtmlForQueryStatistics($ServerStatusData));
$response->addHTML('</div>');
exit;

?>
