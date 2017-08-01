<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SignOn Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage SignOn
 */
namespace PMA\libraries\plugins\auth;

use PMA\libraries\plugins\AuthenticationPlugin;
use PMA;

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
     * @return boolean   always true (no return indeed)
     */
    public function auth()
    {
        unset($_SESSION['LAST_SIGNON_URL']);
        if (empty($GLOBALS['cfg']['Server']['SignonURL'])) {
            PMA_fatalError('You must set SignonURL!');
        } else {
            PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['SignonURL']);
        }

        if (!defined('TESTSUITE')) {
            exit();
        } else {
            return false;
        }
    }

    /**
     * Gets advanced authentication settings
     *
     * @global string $PHP_AUTH_USER the username
     * @global string $PHP_AUTH_PW   the password
     *
     * @return boolean   whether we get authentication settings or not
     */
    public function authCheck()
    {
        global $PHP_AUTH_USER, $PHP_AUTH_PW;

        /* Check if we're using same signon server */
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

        /* Session cookie params */
        $session_cookie_params = (array) $GLOBALS['cfg']['Server']['SignonCookieParams'];

        /* Login URL */
        $signon_url = $GLOBALS['cfg']['Server']['SignonURL'];

        /* Current host */
        $single_signon_host = $GLOBALS['cfg']['Server']['host'];

        /* Current port */
        $single_signon_port = $GLOBALS['cfg']['Server']['port'];

        /* No configuration updates */
        $single_signon_cfgupdate = array();

        /* Handle script based auth */
        if (!empty($script_name)) {
            if (!@file_exists($script_name)) {
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
            $old_cookie_params = session_get_cookie_params();
            if (!defined('TESTSUITE')) {
                session_write_close();
            }

            /* Sanitize cookie params */
            $defaultCookieParams = function($key){
                switch ($key) {
                    case 'lifetime': return 0;
                    case 'path': return '/';
                    case 'domain': return '';
                    case 'secure': return false;
                    case 'httponly': return false;
                }
                return null;
            };
            foreach (array('lifetime', 'path', 'domain', 'secure', 'httponly') as $key) {
                if (!isset($session_cookie_params[$key]))
                    $session_cookie_params[$key] = $defaultCookieParams($key);
            }

            /* Load single signon session */
            if (!defined('TESTSUITE')) {
                session_set_cookie_params($session_cookie_params['lifetime'], $session_cookie_params['path'], $session_cookie_params['domain'], $session_cookie_params['secure'], $session_cookie_params['httponly']);
                session_name($session_name);
                session_id($_COOKIE[$session_name]);
                session_start();
            }

            /* Clear error message */
            unset($_SESSION['PMA_single_signon_error_message']);

            /* Grab credentials if they exist */
            if (isset($_SESSION['PMA_single_signon_user'])) {
                $PHP_AUTH_USER = $_SESSION['PMA_single_signon_user'];
            }
            if (isset($_SESSION['PMA_single_signon_password'])) {
                $PHP_AUTH_PW = $_SESSION['PMA_single_signon_password'];
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
            if (!defined('TESTSUITE')) {
                session_write_close();
            }

            /* Restart phpMyAdmin session */
            if (!defined('TESTSUITE')) {
                session_set_cookie_params($old_cookie_params['lifetime'], $old_cookie_params['path'], $old_cookie_params['domain'], $old_cookie_params['secure'], $old_cookie_params['httponly']);
                session_name($old_session);
                if (!empty($old_id)) {
                    session_id($old_id);
                }
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
            PMA\libraries\Util::clearUserCache();
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
     * @global  array  $cfg                   the valid servers settings
     * @global  string $PHP_AUTH_USER         the current username
     * @global  string $PHP_AUTH_PW           the current password
     *
     * @return boolean   always true
     */
    public function authSetUser()
    {
        global $cfg;
        global $PHP_AUTH_USER, $PHP_AUTH_PW;

        $cfg['Server']['user'] = $PHP_AUTH_USER;
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
            if (!defined('TESTSUITE')) {
                /* End current session */
                session_write_close();

                /* Load single signon session */
                session_name($session_name);
                session_id($_COOKIE[$session_name]);
                session_start();
            }

            /* Set error message */
            $_SESSION['PMA_single_signon_error_message'] = $this->getErrorMessage();
        }
        $this->auth();
    }

    /**
     * Returns URL for login form.
     *
     * @return string
     */
    public function getLoginFormURL()
    {
        return $GLOBALS['cfg']['Server']['SignonURL'];
    }
}
