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
        if (strlen($cfg['ProxyUrl'])) {
            $context = array(
                'http' => array(
                    'proxy' => $cfg['ProxyUrl'],
                    'request_fulluri' => true
                )
            );
            if (strlen($cfg['ProxyUser'])) {
                $auth = base64_encode(
                    $cfg['ProxyUser'] . ':' . $cfg['ProxyPass']
                );
                $context['http']['header'] = 'Proxy-Authorization: Basic ' . $auth;
            }
            $response = file_get_contents(
                $file,
                false,
                stream_context_create($context)
            );
        } else {
            $response = file_get_contents($file);
        }
    } else if (function_exists('curl_init')) {
        $curl_handle = curl_init($file);
        if (strlen($cfg['ProxyUrl'])) {
            curl_setopt($curl_handle, CURLOPT_PROXY, $cfg['ProxyUrl']);
            if (strlen($cfg['ProxyUser'])) {
                curl_setopt(
                    $curl_handle,
                    CURLOPT_PROXYUSERPWD,
                    $cfg['ProxyUser'] . ':' . $cfg['ProxyPass']
                );
            }
        }
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl_handle);
    }
}

// Always send the correct headers
header('Content-type: application/json; charset=UTF-8');

// Save and forward the response only if in valid format
$data = json_decode($response);
if (is_object($data) && strlen($data->version) && strlen($data->date)) {
    if ($save) {
        $_SESSION['cache']['version_check'] = array(
            'response' => $response,
            'timestamp' => time()
        );
    }
    echo $response;
}

?>
