<?php
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */

declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

global $containerBuilder, $dbi;

// phpcs:disable PSR1.Files.SideEffects
define('PMA_MINIMUM_COMMON', true);
// phpcs:enable

require_once ROOT_PATH . 'libraries/common.inc.php';

// Load database service because services.php is not available here
$dbi = DatabaseInterface::load();
$containerBuilder->set(DatabaseInterface::class, $dbi);

// Only output the http headers
$response = Response::getInstance();
$response->getHeader()->sendHttpHeaders();
$response->disable();

if (! Core::isValid($_GET['url'])
    || ! preg_match('/^https:\/\/[^\n\r]*$/', $_GET['url'])
    || ! Core::isAllowedDomain($_GET['url'])
) {
    Core::sendHeaderLocation('./');
} else {
    // JavaScript redirection is necessary. Because if header() is used
    //  then web browser sometimes does not change the HTTP_REFERER
    //  field and so with old URL as Referer, token also goes to
    //  external site.
    $template = $containerBuilder->get('template');
    echo $template->render('javascript/redirect', [
        'url' => Sanitize::escapeJsString($_GET['url']),
    ]);
    // Display redirecting msg on screen.
    // Do not display the value of $_GET['url'] to avoid showing injected content
    echo __('Taking you to the target site.');
}
die;
