<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\VersionInformation;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

$_GET['ajax_request'] = 'true';

require_once ROOT_PATH . 'libraries/common.inc.php';

// Disabling standard response.
Response::getInstance()->disable();

// Always send the correct headers
Core::headerJSON();

$versionInformation = new VersionInformation();
$versionDetails = $versionInformation->getLatestVersion();

if (empty($versionDetails)) {
    echo json_encode([]);
} else {
    $latestCompatible = $versionInformation->getLatestCompatibleVersion(
        $versionDetails->releases
    );
    $version = '';
    $date = '';
    if ($latestCompatible != null) {
        $version = $latestCompatible['version'];
        $date = $latestCompatible['date'];
    }
    echo json_encode(
        [
            'version' => ! empty($version) ? $version : '',
            'date' => ! empty($date) ? $date : '',
        ]
    );
}
