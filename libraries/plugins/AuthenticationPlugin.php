<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the authentication plugins
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\plugins;

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
    abstract public function auth();

    /**
     * Gets advanced authentication settings
     *
     * @return boolean
     */
    abstract public function authCheck();

    /**
     * Set the user and password after last checkings if required
     *
     * @return boolean
     */
    abstract public function authSetUser();

    /**
     * Stores user credentials after successful login.
     *
     * @return void
     */
    public function storeUserCredentials()
    {
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @return boolean
     */
    abstract public function authFails();

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

        /* delete user's choices that were stored in session */
        $_SESSION = array();
        if (!defined('TESTSUITE')) {
            session_destroy();
        }

        /* Redirect to login form (or configured URL) */
        PMA_sendHeaderLocation($redirect_url);
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
                return PMA_sanitize($dbi_error);
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
}
