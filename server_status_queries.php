<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Displays query statistics for the server
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Queries;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/replication.inc.php';

$serverStatusData = new Data();

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();

$scripts->addFile('server_status_sorter');
$scripts->addFile('server_status_queries');

// Add the html content to the response
$response->addHTML('<div>');
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(Queries::getHtmlForQueryStatistics($serverStatusData));
} else {
    $response->addHTML(
        Message::error(
            __('Not enough privilege to view query statistics.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');
exit;
