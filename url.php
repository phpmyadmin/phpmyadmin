<?php
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

if (PHP_VERSION_ID < 70205) {
    die('<p>PHP 7.2.5+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require_once ROOT_PATH . 'libraries/vendor_config.php';

/**
 * Activate autoloader
 */
if (! @is_readable(AUTOLOAD_FILE)) {
    die(
        '<p>File <samp>' . AUTOLOAD_FILE . '</samp> missing or not readable.</p>'
        . '<p>Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">'
        . 'install library files</a>.</p>'
    );
}

require AUTOLOAD_FILE;

global $containerBuilder, $dbi;

$isMinimumCommon = true;

require_once ROOT_PATH . 'libraries/common.inc.php';

Common::run();

// Load database service because services.php is not available here
$dbi = DatabaseInterface::load();
$containerBuilder->set(DatabaseInterface::class, $dbi);

// Only output the http headers
$response = ResponseRenderer::getInstance();
$response->getHeader()->sendHttpHeaders();
$response->disable();

if (
    ! Core::isValid($_GET['url'])
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
