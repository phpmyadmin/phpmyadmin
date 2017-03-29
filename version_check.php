<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A caching proxy for retrieving version information from https://www.phpmyadmin.net/
 *
 * @package PhpMyAdmin
 */

// Sets up the session
use PMA\libraries\VersionInformation;
use PMA\libraries\Response;

$_GET['ajax_request'] = 'true';

require_once 'libraries/common.inc.php';

// Disabling standard response.
Response::getInstance()->disable();

// Always send the correct headers
PMA_headerJSON();

$versionInformation = new VersionInformation();
$versionDetails = $versionInformation->getLatestVersion();

if (empty($versionDetails)) {
    echo json_encode(array());
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
        array(
            'version' => (! empty($version) ? $version : ''),
            'date' => (! empty($date) ? $date : ''),
        )
    );
}
