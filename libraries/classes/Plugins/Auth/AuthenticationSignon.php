<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SignOn Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage SignOn
 */
namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Core;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Util;

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
    public function showLoginForm()
    {
        unset($_SESSION['LAST_SIGNON_URL']);
        if (empty($GLOBALS['cfg']['Server']['SignonURL'])) {
            Core::fatalError('You must set SignonURL!');
        } else {
            Core::sendHeaderLocation($GLOBALS['cfg']['Server']['SignonURL']);
        }

        if (!defined('TESTSUITE')) {
            exit();
        } else {
            return false;
        }
    }

    /**
     * Set cookie params
     *
     * @param array $sessionCookieParams The cookie params
     * @return void
     */
    public function setCookieParams(array $sessionCookieParams = null)
    {
        /* Session cookie params from config */
        if ($sessionCookieParams === null) {
            $sessionCookieParams = (array) $GLOBALS['cfg']['Server']['SignonCookieParams'];
        }

        /* Sanitize cookie params */
        $defaultCookieParams = function ($key) {
            switch ($key) {
                case 'lifetime':
                    return 0;
                case 'path':
                    return '/';
                case 'domain':
                    return '';
                case 'secure':
                    return false;
                case 'httponly':
                    return false;
            }
            return null;
        };

        foreach (array('lifetime', 'path', 'domain', 'secure', 'httponly') as $key) {
            if (! isset($sessionCookieParams[$key])) {
                $sessionCookieParams[$key] = $defaultCookieParams($key);
            }
        }

        if (isset($sessionCookieParams['samesite'])
            && ! in_array($sessionCookieParams['samesite'], array('Lax', 'Strict'))) {
                // Not a valid value for samesite
                unset($sessionCookieParams['samesite']);
        }

        if (version_compare(phpversion(), '7.3.0', '>=')) {
            session_set_cookie_params($sessionCookieParams);
        }

        session_set_cookie_params(
            $sessionCookieParams['lifetime'],
            $sessionCookieParams['path'],
            $sessionCookieParams['domain'],
            $sessionCookieParams['secure'],
            $sessionCookieParams['httponly']
        );
    }

    /**
     * Gets authentication credentials
     *
     * @return boolean   whether we get authentication settings or not
     */
    public function readCredentials()
    {
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
                Core::fatalError(
                    __('Can not find signon authentication script:')
                    . ' ' . $script_name
                );
            }
            include $script_name;

            list ($this->user, $this->password)
                = get_login_credentials($GLOBALS['cfg']['Server']['user']);
        } elseif (isset($_COOKIE[$session_name])) { /* Does session exist? */
            /* End current session */
            $old_session = session_name();
            $old_id = session_id();
            $oldCookieParams = session_get_cookie_params();
            if (!defined('TESTSUITE')) {
                session_write_close();
            }
            /* Load single signon session */
            if (!defined('TESTSUITE')) {
                $this->setCookieParams();
                session_name($session_name);
                session_id($_COOKIE[$session_name]);
                session_start();
            }

            /* Clear error message */
            unset($_SESSION['PMA_single_signon_error_message']);

            /* Grab credentials if they exist */
            if (isset($_SESSION['PMA_single_signon_user'])) {
                $this->user = $_SESSION['PMA_single_signon_user'];
            }
            if (isset($_SESSION['PMA_single_signon_password'])) {
                $this->password = $_SESSION['PMA_single_signon_password'];
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
                $this->setCookieParams($oldCookieParams);
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
                $_SESSION[' HMAC_secret '] = Util::generateRandom(16);
            }

            /**
             * Clear user cache.
             */
            Util::clearUserCache();
        }

        // Returns whether we get authentication settings or not
        if (empty($this->user)) {
            unset($_SESSION['LAST_SIGNON_URL']);

            return false;
        }

        $_SESSION['LAST_SIGNON_URL'] = $GLOBALS['cfg']['Server']['SignonURL'];

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     *
     * @return void
     */
    public function showFailure($failure)
    {
        parent::showFailure($failure);

        /* Session name */
        $session_name = $GLOBALS['cfg']['Server']['SignonSession'];

        /* Does session exist? */
        if (isset($_COOKIE[$session_name])) {
            if (!defined('TESTSUITE')) {
                /* End current session */
                session_write_close();

                /* Load single signon session */
                $this->setCookieParams();
                session_name($session_name);
                session_id($_COOKIE[$session_name]);
                session_start();
            }

            /* Set error message */
            $_SESSION['PMA_single_signon_error_message'] = $this->getErrorMessage($failure);
        }
        $this->showLoginForm();
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
