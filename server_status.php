<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server status page: processes, connections and traffic
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status;
use PhpMyAdmin\Server\Status\Data;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/server_common.inc.php';

/**
 * Replication library
 */
require_once ROOT_PATH . 'libraries/replication.inc.php';

/**
 * start output
 */
$response = Response::getInstance();
$response->addHTML('<div>');

$serverStatusData = new Data();
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(Status::getHtml($serverStatusData));
} else {
    $response->addHTML(
        Message::error(
            __('Not enough privilege to view server status.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');
exit;
