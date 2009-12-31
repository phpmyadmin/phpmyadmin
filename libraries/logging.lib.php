<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Logging functionality for webserver.
 *
 * This includes web server specific code to log some information.
 *
 * @version $Id$
 * @package phpMyAdmin
 */

function PMA_log_user($user, $status = 'ok'){
    if (function_exists('apache_note')) {
        apache_note('userID', $user);
        apache_note('userStatus', $status);
    }
}

?>
