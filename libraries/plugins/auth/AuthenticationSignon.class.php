<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SignOn Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage SignOn
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the authentication interface */
require_once 'libraries/plugins/AuthenticationPlugin.class.php';

/**
 * Handles the SignOn authentication method
 *
 * @package PhpMyAdmin-Authentication
 */
class AuthenticationSignon extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     *
     * @global  string    the font face to use in case of failure
     * @global  string    the default font size to use in case of failure
     * @global  string    the big font size to use in case of failure
     *
     * @return boolean   always true (no return indeed)
     */
    public function auth()
    {
        unset($_SESSION['LAST_SIGNON_URL']);
        if (empty($GLOBALS['cfg']['Server']['SignonURL'])) {
            PMA_fatalError('You must set SignonURL!');
        } elseif (! empty($_REQUEST['old_usr'])
            && ! empty($GLOBALS['cfg']['Server']['LogoutURL'])
        ) {
            /* Perform logout to custom URL */
            PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['LogoutURL']);
        } else {
            PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['SignonURL']);
        }

        if (! defined('TESTSUITE')) {
            exit();
        } else {
            return false;
        }
    }

    /**
     * Gets advanced authentication settings
     *
     * @global  string $PHP_AUTH_USER the username if register_globals is on
     * @global  string $PHP_AUTH_PW   the password if register_globals is on
     * @global  array                 the array of server variables if
     *                                register_globals is off
     * @global  array                 the array of environment variables if
     *                                register_globals is off
     * @global  string                the username for the ? server
     * @global  string                the password for the ? server
     * @global  string                the username for the WebSite Professional
     *                                server
     * @global  string                the password for the WebSite Professional
     *                                server
     * @global  string                the username of the user who logs out
     *
     * @return boolean   whether we get authentication settings or not
     */
    public function authCheck()
    {
        global $PHP_AUTH_USER, $PHP_AUTH_PW;

        /* Check if we're using same sigon server */
        $signon_url = $GLOBALS['cfg']['Server']['SignonURL'];
        if (isset($_SESSION['LAST_SIGNON_URL'])
            && $_SESSION['LAST_SIGNON_URL'] != $signon_url
        ) {
            return false;
        }

        /* Script name */
        $script_name = $GLOBALS['cfg']['Server']['SignonScript'];

        /* Session name */
        $session_name = $GLOBALS['cfg']['Server']['SignonSession'];

        /* Login URL */
        $signon_url = $GLOBALS['cfg']['Server']['SignonURL'];

        /* Current host */
        $single_signon_host = $GLOBALS['cfg']['Server']['host'];

        /* Current port */
        $single_signon_port = $GLOBALS['cfg']['Server']['port'];

        /* No configuration updates */
        $single_signon_cfgupdate = array();

        /* Are we requested to do logout? */
        $do_logout = !empty($_REQUEST['old_usr']);

        /* Handle script based auth */
        if (!empty($script_name)) {
            if (! file_exists($script_name)) {
                PMA_fatalError(
                    __('Can not find signon authentication script:')
                    . ' ' . $script_name
                );
            }
            include $script_name;

            list ($PHP_AUTH_USER, $PHP_AUTH_PW)
                = get_login_credentials($GLOBALS['cfg']['Server']['user']);

        } elseif (isset($_COOKIE[$session_name])) { /* Does session exist? */
            /* End current session */
            $old_session = session_name();
            $old_id = session_id();
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Load single signon session */
            session_name($session_name);
            session_id($_COOKIE[$session_name]);
            if (! defined('TESTSUITE')) {
                session_start();
            }

            /* Clear error message */
            unset($_SESSION['PMA_single_signon_error_message']);

            /* Grab credentials if they exist */
            if (isset($_SESSION['PMA_single_signon_user'])) {
                if ($do_logout) {
                    $PHP_AUTH_USER = '';
                } else {
                    $PHP_AUTH_USER = $_SESSION['PMA_single_signon_user'];
                }
            }
            if (isset($_SESSION['PMA_single_signon_password'])) {
                if ($do_logout) {
                    $PHP_AUTH_PW = '';
                } else {
                    $PHP_AUTH_PW = $_SESSION['PMA_single_signon_password'];
                }
            }
            if (isset($_SESSION['PMA_single_signon_host'])) {
                $single_signon_host = $_SESSION['PMA_single_signon_host'];
            }

            if (isset($_SESSION['PMA_single_signon_port'])) {
                $single_signon_port = $_SESSION['PMA_single_signon_port'];
            }

            if (isset($_SESSION['PMA_single_signon_cfgupdate'])) {
                $single_signon_cfgupdate = $_SESSION['PMA_single_signon_cfgupdate'];
            }


            /* Also get token as it is needed to access subpages */
            if (isset($_SESSION['PMA_single_signon_token'])) {
                /* No need to care about token on logout */
                $pma_token = $_SESSION['PMA_single_signon_token'];
            }

            /* End single signon session */
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Restart phpMyAdmin session */
            session_name($old_session);
            if (!empty($old_id)) {
                session_id($old_id);
            }
            if (! defined('TESTSUITE')) {
                session_start();
            }

            /* Set the single signon host */
            $GLOBALS['cfg']['Server']['host'] = $single_signon_host;

            /* Set the single signon port */
            $GLOBALS['cfg']['Server']['port'] = $single_signon_port;

            /* Configuration update */
            $GLOBALS['cfg']['Server'] = array_merge(
                $GLOBALS['cfg']['Server'],
                $single_signon_cfgupdate
            );

            /* Restore our token */
            if (!empty($pma_token)) {
                $_SESSION[' PMA_token '] = $pma_token;
            }

            /**
             * Clear user cache.
             */
            PMA_Util::clearUserCache();
        }

        // Returns whether we get authentication settings or not
        if (empty($PHP_AUTH_USER)) {
            unset($_SESSION['LAST_SIGNON_URL']);
            return false;
        } else {
            $_SESSION['LAST_SIGNON_URL'] = $GLOBALS['cfg']['Server']['SignonURL'];
            return true;
        }
    }

    /**
     * Set the user and password after last checkings if required
     *
     * @global  array   $cfg           the valid servers settings
     * @global  integer                the id of the current server
     * @global  array                  the current server settings
     * @global  string  $PHP_AUTH_USER the current username
     * @global  string  $PHP_AUTH_PW   the current password
     *
     * @return boolean   always true
     */
    public function authSetUser()
    {
        global $cfg;
        global $PHP_AUTH_USER, $PHP_AUTH_PW;

        $cfg['Server']['user']     = $PHP_AUTH_USER;
        $cfg['Server']['password'] = $PHP_AUTH_PW;

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @return boolean   always true (no return indeed)
     */
    public function authFails()
    {
        /* Session name */
        $session_name = $GLOBALS['cfg']['Server']['SignonSession'];

        /* Does session exist? */
        if (isset($_COOKIE[$session_name])) {
            /* End current session */
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Load single signon session */
            session_name($session_name);
            session_id($_COOKIE[$session_name]);
            if (! defined('TESTSUITE')) {
                session_start();
            }

            /* Set error message */
            if (! empty($GLOBALS['login_without_password_is_forbidden'])) {
                $_SESSION['PMA_single_signon_error_message'] = __(
                    'Login without a password is forbidden by configuration '
                    . '(see AllowNoPassword)'
                );
            } elseif (! empty($GLOBALS['allowDeny_forbidden'])) {
                $_SESSION['PMA_single_signon_error_message'] = __('Access denied!');
            } elseif (! empty($GLOBALS['no_activity'])) {
                $_SESSION['PMA_single_signon_error_message'] = sprintf(
                    __('No activity within %s seconds; please log in again.'),
                    $GLOBALS['cfg']['LoginCookieValidity']
                );
            } elseif ($GLOBALS['dbi']->getError()) {
                $_SESSION['PMA_single_signon_error_message'] = PMA_sanitize(
                    $GLOBALS['dbi']->getError()
                );
            } else {
                $_SESSION['PMA_single_signon_error_message'] = __(
                    'Cannot log in to the MySQL server'
                );
            }
        }
        $this->auth();
    }

    /**
     * This method is called when any PluginManager to which the observer
     * is attached calls PluginManager::notify()
     *
     * @param SplSubject $subject The PluginManager notifying the observer
     *                            of an update.
     *
     * @return void
     */
    public function update (SplSubject $subject)
    {
    }
}
