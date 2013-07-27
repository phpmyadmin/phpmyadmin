<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Proxy for retrieving version information from phpmyadmin.net
 *
 * @package PhpMyAdmin
 */

header('Content-Type: application/json');

$file = 'http://www.phpmyadmin.net/home_page/version.json';

$response = '{}';

if (ini_get('allow_url_fopen')) {
    $response = file_get_contents($file);
} else if (function_exists('curl_init')) {
    $curl_handle = curl_init($file);
    curl_setopt(
        $curl_handle,
        CURLOPT_RETURNTRANSFER,
        true
    );
    $response = curl_exec($curl_handle);
}

// check if the retrieved version.json file is a valid json object,
// before encoding it again and sending it to the browser
$data = json_decode($response);
if (is_object($data) && strlen($data->version) && strlen($data->date)) {
    echo json_encode(
        array('version' => $data->version, 'date' => $data->date)
    );
}
?>
