<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/* PHP 5.4 stores upload progress data only in the default session.
 * After calling session_name(), we won't find the progress data anymore.
 *
 * https://bugs.php.net/bug.php?id=64075
 *
 * The bug should be somewhere in
 * https://github.com/php/php-src/blob/master/ext/session/session.c#L2342
 *
 * Until this is fixed, we need to load the default session to load the data,
 * export the upload progress information from there,
 * and re-import after switching to our session.
 */

if (version_compare(PHP_VERSION, '5.4.0', '>=')
    && ini_get('session.upload_progress.enabled')
) {

    $sessionupload = array();
    define('UPLOAD_PREFIX', ini_get('session.upload_progress.prefix'));

    session_start();
    foreach ($_SESSION as $key => $value) {
        // only copy session-prefixed data
        if (substr($key, 0, strlen(UPLOAD_PREFIX)) == UPLOAD_PREFIX) {
            $sessionupload[$key] = $value;
        }
    }
    // PMA will kill all variables, so let's use a constant
    define('SESSIONUPLOAD', serialize($sessionupload));
    session_write_close();

    session_name('phpMyAdmin');
    session_id($_COOKIE['phpMyAdmin']);
}

define('PMA_MINIMUM_COMMON', 1);

require_once 'libraries/common.inc.php';
require_once 'libraries/display_import_ajax.lib.php';

if (defined('SESSIONUPLOAD')) {
    // write sessionupload back into the loaded PMA session

    $sessionupload = unserialize(SESSIONUPLOAD);
    foreach ($sessionupload as $key => $value) {
        $_SESSION[$key] = $value;
    }

    // remove session upload data that are not set anymore
    foreach ($_SESSION as $key => $value) {
        if (substr($key, 0, strlen(UPLOAD_PREFIX)) == UPLOAD_PREFIX
            && ! isset($sessionupload[$key])
        ) {
            unset($_SESSION[$key]);
        }
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
PMA_noCacheHeader();

// $GLOBALS["message"] is used for asking for an import message
if (isset($GLOBALS["message"]) && $GLOBALS["message"]) {

    header('Content-type: text/html');

    // wait 0.3 sec before we check for $_SESSION variable,
    // which is set inside import.php
    usleep(300000);

    // wait until message is available
    while ($_SESSION['Import_message']['message'] == null) {
        usleep(250000); // 0.25 sec
    }

    echo $_SESSION['Import_message']['message'];
    echo '<fieldset class="tblFooters">' . "\n";
    echo '    [ <a href="' . $_SESSION['Import_message']['go_back_url']
        . '">' . __('Back') . '</a> ]' . "\n";
    echo '</fieldset>'."\n";

} else {
    PMA_importAjaxStatus($GLOBALS["id"]);
}
?>
