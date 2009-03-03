<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Logging functionality for webserver.
 *
 * This includes web server specific code to log some information.
 *
 * @version $Id: common.inc.php 12268 2009-03-02 16:19:36Z lem9 $
 * @package phpMyAdmin
 */

function PMA_log_user($user, $status = 'ok'){
    if (function_exists('apache_note')) {
        apache_note('userID', $user);
        apache_note('userStatus', $status);
    }
}

?>
