<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

require_once './libraries/common.inc.php';
require_once './libraries/display_import_ajax.lib.php';

// AJAX requests can't be cached!
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 11 Jan 1991 06:30:00 GMT"); // Date in the past

// $GLOBALS["message"] is used for asking for an import message
if (isset($GLOBALS["message"]) && $GLOBALS["message"]) {

    header('Content-type: text/html');

	usleep(300000); // wait 0.3 sec before we check for $_SESSION variable, which is set inside import.php 
    while ($_SESSION['Import_message']['message'] == null) { // wait until message is available 
        usleep(250000); // 0.25 sec
    }

    echo $_SESSION['Import_message']['message'];
    echo '<fieldset class="tblFooters">' . "\n";
    echo '	[ <a href="' . $_SESSION['Import_message']['go_back_url'] . '">' . $GLOBALS["strBack"] . '</a> ]' . "\n";
    echo '</fieldset>'."\n";

} else {
    PMA_importAjaxStatus($GLOBALS["id"]);
}
?>
