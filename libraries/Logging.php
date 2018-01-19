<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Logging functionality for webserver.
 *
 * This includes web server specific code to log some information.
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Misc logging functions
 *
 * @package PhpMyAdmin
 */
class Logging
{
    /**
     * Logs user information to webserver logs.
     *
     * @param string $user   user name
     * @param string $status status message
     *
     * @return void
     */
    public static function logUser($user, $status = 'ok')
    {
        if (function_exists('apache_note')) {
            apache_note('userID', $user);
            apache_note('userStatus', $status);
        }
        if (function_exists('syslog') && $status != 'ok') {
            @openlog('phpMyAdmin', LOG_NDELAY | LOG_PID, LOG_AUTHPRIV);
            @syslog(
                LOG_WARNING,
                'user denied: ' . $user . ' (' . $status . ') from ' .
                PMA_getIp()
            );
            closelog();
        }
    }
}
