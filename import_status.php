<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

require_once './libraries/common.inc.php';
require_once './libraries/display_import_ajax.lib.php';

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
