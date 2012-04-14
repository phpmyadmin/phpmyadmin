<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/* PHP 5.4 stores upload progress data only in the default session.
 * After calling session_name(), we won't find the progress data anymore.

 * The bug should be somewhere in
 * https://github.com/php/php-src/blob/master/ext/session/session.c#L2342

 * Until this is fixed, we need to load the default session to load the data.
 * As we cannot load the phpMyAdmin-session after that, we will try to do
 * an internal POST request to call ourselves in a new instance.
 * That POST request grabs the transmitted upload data and stores them.
 *
 * TODO: The internal HTTP request may fail if the DNS name cannot be resolved
 * or if a firewall blocks outgoing requests on the used port.
 */

if (version_compare(PHP_VERSION, '5.4.0', '>=')
    && ini_get('session.upload_progress.enabled')
) {

    if (!isset($_POST['session_upload_progress'])) {
        $sessionupload = array();
        $prefix = ini_get('session.upload_progress.prefix');

        session_start();
        foreach ($_SESSION as $key => $value) {
            // only copy session-prefixed data
            if (substr($key, 0, strlen($prefix)) == $prefix) {
                $sessionupload[$key] = $value;
            }
        }

        // perform internal self-request
        $url = 'http' .
            ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 's' : '') .
            '://' . $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'];

        if (!function_exists('curl_exec') || !function_exists('getallheaders')) {
            die();
        }
        $headers = @getallheaders();
        if (!isset($headers['Cookie'])) {
            die();
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch, CURLOPT_POSTFIELDS,
            'session_upload_progress=' . rawurlencode(serialize($sessionupload))
        );
        curl_setopt($ch, CURLOPT_COOKIE, $headers['Cookie']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        // to avoid problems with self-signed certs
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        // show the result of the internal request
        echo @curl_exec($ch);
        die();
    }
}

require_once 'libraries/common.inc.php';
require_once 'libraries/display_import_ajax.lib.php';

if (isset($_POST['session_upload_progress'])) {
    // this is the internal request response
    // restore sessionupload from the POSTed data (see above),
    // then write sessionupload back into the loaded session

    $sessionupload = unserialize($_POST['session_upload_progress']);
    foreach ($sessionupload as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

/**
 * Sets globals from $_GET
 */
$get_params = array(
    'message',
    'id'
);
foreach ($get_params as $one_get_param) {
    if (isset($_GET[$one_get_param])) {
        $GLOBALS[$one_get_param] = $_GET[$one_get_param];
    }
}

// AJAX requests can't be cached!
PMA_no_cache_header();

// $GLOBALS["message"] is used for asking for an import message
if (isset($GLOBALS["message"]) && $GLOBALS["message"]) {

    header('Content-type: text/html');

    // wait 0.3 sec before we check for $_SESSION variable, which is set inside import.php
    usleep(300000);

    // wait until message is available
    while ($_SESSION['Import_message']['message'] == null) {
        usleep(250000); // 0.25 sec
    }

    echo $_SESSION['Import_message']['message'];
    echo '<fieldset class="tblFooters">' . "\n";
    echo '    [ <a href="' . $_SESSION['Import_message']['go_back_url'] . '">' . __('Back') . '</a> ]' . "\n";
    echo '</fieldset>'."\n";

} else {
    PMA_importAjaxStatus($GLOBALS["id"]);
}
?>
