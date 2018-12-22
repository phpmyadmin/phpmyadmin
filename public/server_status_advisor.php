<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the advisor feature
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Advisor;
use PhpMyAdmin\Server\Status\Data;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

$serverStatusData = new Data();

$response = Response::getInstance();
$scripts = $response->getHeader()->getScripts();
$scripts->addFile('server_status_advisor.js');

/**
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($serverStatusData->getMenuHtml());
if ($serverStatusData->dataLoaded) {
    $response->addHTML(Advisor::getHtml());
} else {
    $response->addHTML(
        Message::error(
            __('Not enough privilege to view the advisor.')
        )->getDisplay()
    );
}
$response->addHTML('</div>');

exit;
