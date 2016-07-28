<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A caching proxy for retrieving version information from phpmyadmin.net
 *
 * @package PhpMyAdmin
 */

$_GET['ajax_request'] = 'true';

// Sets up the session
require_once 'libraries/common.inc.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/VersionInformation.php';

require_once 'libraries/common.inc.php';

// Disabling standard response.
PMA_Response::getInstance()->disable();

// Always send the correct headers
header('Content-type: application/json; charset=UTF-8');

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

?>
