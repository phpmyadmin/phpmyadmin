<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Proxy for retrieving version information from phpmyadmin.net
 *
 * @package PhpMyAdmin
 */

$file = 'http://www.phpmyadmin.net/home_page/version.json';
if (ini_get('allow_url_fopen')) {
    echo file_get_contents($file);
} else if (function_exists('curl_init')) {
    curl_exec(curl_init($file));
}

?>
