<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * A caching proxy for retrieving version information from phpmyadmin.net
 *
 * @package PhpMyAdmin
 */

// Sets up the session
define('PMA_MINIMUM_COMMON', true);
require_once 'libraries/common.inc.php';
require_once 'libraries/Util.class.php';

// Always send the correct headers
header('Content-type: application/json; charset=UTF-8');

$version = PMA_Util::getLatestVersion();

if (empty($version)) {
    echo json_encode(array());
} else {
    echo json_encode(
        array(
            'version' => (! empty($version->version) ? $version->version : ''),
            'date' => (! empty($version->date) ? $version->date : ''),
        )
    );
}

?>
