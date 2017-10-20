<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the authentication plugins
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Session;
use PhpMyAdmin\Url;

/**
 * Provides a common interface that will have to be implemented by all of the
 * authentication plugins.
 *
 * @package PhpMyAdmin
 */
abstract class AuthenticationPlugin
{
    /**
     * Displays authentication form
     *
     * @return boolean
     */
    abstract public function showLoginForm();

    /**
     * Gets authentication credentials
     *
     * @return boolean
     */
    abstract public function readCredentials();

    /**
     * Set the user and password after last checkings if required
     *
     * @return boolean
     */
    public function storeCredentials()
    {
        $this->setSessionAccessTime();

        return true;
    }

    /**
     * Stores user credentials after successful login.
     *
     * @return void
     */
    public function rememberCredentials()
    {
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @return void
     */
    abstract public function showFailure();

    /**
     * Perform logout
     *
     * @return void
     */
    public function logOut()
    {
        global $PHP_AUTH_USER, $PHP_AUTH_PW;

        /* Obtain redirect URL (before doing logout) */
        if (! empty($GLOBALS['cfg']['Server']['LogoutURL'])) {
            $redirect_url = $GLOBALS['cfg']['Server']['LogoutURL'];
        } else {
            $redirect_url = $this->getLoginFormURL();
        }

        /* Clear credentials */
        $PHP_AUTH_USER = '';
        $PHP_AUTH_PW = '';

        /*
         * Get a logged-in server count in case of LoginCookieDeleteAll is disabled.
         */
        $server = 0;
        if ($GLOBALS['cfg']['LoginCookieDeleteAll'] === false
            && $GLOBALS['cfg']['Server']['auth_type'] == 'cookie'
        ) {
            foreach ($GLOBALS['cfg']['Servers'] as $key => $val) {
                if (isset($_COOKIE['pmaAuth-' . $key])) {
                    $server = $key;
                }
            }
        }

        if ($server === 0) {
            /* delete user's choices that were stored in session */
            if (! defined('TESTSUITE')) {
                $_SESSION = array();
                session_destroy();
            }

            /* Redirect to login form (or configured URL) */
            Core::sendHeaderLocation($redirect_url);
        } else {
            /* Redirect to other autenticated server */
            $_SESSION['partial_logout'] = true;
            Core::sendHeaderLocation(
                './index.php' . Url::getCommonRaw(array('server' => $server))
            );
        }
    }

    /**
     * Returns URL for login form.
     *
     * @return string
     */
    public function getLoginFormURL()
    {
        return './index.php';
    }

    /**
     * Returns error message for failed authentication.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        if (!empty($GLOBALS['login_without_password_is_forbidden'])) {
            return __(
                'Login without a password is forbidden by configuration'
                . ' (see AllowNoPassword)'
            );
        } elseif (!empty($GLOBALS['allowDeny_forbidden'])) {
            return __('Access denied!');
        } elseif (!empty($GLOBALS['no_activity'])) {
            return sprintf(
                __('No activity within %s seconds; please log in again.'),
                intval($GLOBALS['cfg']['LoginCookieValidity'])
            );
        } else {
            $dbi_error = $GLOBALS['dbi']->getError();
            if (!empty($dbi_error)) {
                return htmlspecialchars($dbi_error);
            } elseif (isset($GLOBALS['errno'])) {
                return '#' . $GLOBALS['errno'] . ' '
                . __('Cannot log in to the MySQL server');
            } else {
                return __('Cannot log in to the MySQL server');
            }
        }
    }

    /**
     * Callback when user changes password.
     *
     * @param string $password New password to set
     *
     * @return void
     */
    public function handlePasswordChange($password)
    {
    }

    /**
     * Store session access time in session.
     *
     * Tries to workaround PHP 5 session garbage collection which
     * looks at the session file's last modified time
     *
     * @return void
     */
    public function setSessionAccessTime()
    {
        if (isset($_REQUEST['guid'])) {
            $guid = (string)$_REQUEST['guid'];
        } else {
            $guid = 'default';
        }
        if (isset($_REQUEST['access_time'])) {
            // Ensure access_time is in range <0, LoginCookieValidity + 1>
            // to avoid excessive extension of validity.
            //
            // Negative values can cause session expiry extension
            // Too big values can cause overflow and lead to same
            $time = time() - min(max(0, intval($_REQUEST['access_time'])), $GLOBALS['cfg']['LoginCookieValidity'] + 1);
        } else {
            $time = time();
        }
        $_SESSION['browser_access_time'][$guid] = $time;
    }

    /**
     * High level authentication interface
     *
     * Gets the credentials or shows login form if necessary
     *
     * @return void
     */
     public function authenticate()
     {
        if (! $this->readCredentials()) {
            /* Force generating of new session on login */
            Session::secure();
            $this->showLoginForm();
        } else {
            $this->storeCredentials();
        }

        $this->checkRules();
    }

    /**
     * Check configuration defined restrictions for authentication
     *
     * @return void
     */
    public function checkRules()
    {
        global $cfg;

        // Check IP-based Allow/Deny rules as soon as possible to reject the
        // user based on mod_access in Apache
        if (isset($cfg['Server']['AllowDeny'])
            && isset($cfg['Server']['AllowDeny']['order'])
        ) {
            $allowDeny_forbidden         = false; // default
            if ($cfg['Server']['AllowDeny']['order'] == 'allow,deny') {
                $allowDeny_forbidden     = true;
                if (IpAllowDeny::allowDeny('allow')) {
                    $allowDeny_forbidden = false;
                }
                if (IpAllowDeny::allowDeny('deny')) {
                    $allowDeny_forbidden = true;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] == 'deny,allow') {
                if (IpAllowDeny::allowDeny('deny')) {
                    $allowDeny_forbidden = true;
                }
                if (IpAllowDeny::allowDeny('allow')) {
                    $allowDeny_forbidden = false;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] == 'explicit') {
                if (IpAllowDeny::allowDeny('allow') && ! IpAllowDeny::allowDeny('deny')) {
                    $allowDeny_forbidden = false;
                } else {
                    $allowDeny_forbidden = true;
                }
            } // end if ... elseif ... elseif

            // Ejects the user if banished
            if ($allowDeny_forbidden) {
                Logging::logUser($cfg['Server']['user'], 'allow-denied');
                $this->showFailure();
            }
        } // end if

        // is root allowed?
        if (! $cfg['Server']['AllowRoot'] && $cfg['Server']['user'] == 'root') {
            $allowDeny_forbidden = true;
            Logging::logUser($cfg['Server']['user'], 'root-denied');
            $this->showFailure();
        }

        // is a login without password allowed?
        if (! $cfg['Server']['AllowNoPassword']
            && $cfg['Server']['password'] === ''
        ) {
            $login_without_password_is_forbidden = true;
            Logging::logUser($cfg['Server']['user'], 'empty-denied');
            $this->showFailure();
        }
    }
}
