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

// Get response text from phpmyadmin.net or from the session
// Update cache every 6 hours
if (isset($_SESSION['cache']['version_check'])
    && time() < $_SESSION['cache']['version_check']['timestamp'] + 3600 * 6
) {
    $save = false;
    $response = $_SESSION['cache']['version_check']['response'];
} else {
    $save = true;
    $file = 'http://www.phpmyadmin.net/home_page/version.json';
    if (ini_get('allow_url_fopen')) {
        $response = file_get_contents($file);
    } else if (function_exists('curl_init')) {
        $curl_handle = curl_init($file);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl_handle);
    }
}

// Always send the correct headers
header('Content-type: application/json; charset=UTF-8');

// Save and forward the response only if in valid format
$data = json_decode($response);
if (is_object($data)) {
    $latestCompatible = PMA_Util::getLatestCompatibleVersion(
        $data->releases
    );

    $version = '';
    $date = '';
    if ($latestCompatible != null) {
        $version = $latestCompatible['version'];
        $date = $latestCompatible['date'];
    }

    if ($save) {
        $_SESSION['cache']['version_check'] = array(
            'response' => $response,
            'timestamp' => time()
        );
    }
    echo json_encode(
        array(
            'version' => (! empty($version) ? $version : ''),
            'date' => (! empty($date) ? $date : ''),
        )
    );
}

?>
