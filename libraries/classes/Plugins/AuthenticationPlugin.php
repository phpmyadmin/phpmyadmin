<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the authentication plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Session;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
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
     * Username
     *
     * @var string
     */
    public $user = '';

    /**
     * Password
     *
     * @var string
     */
    public $password = '';

    /**
     * @var IpAllowDeny
     */
    protected $ipAllowDeny;

    /**
     * @var Template
     */
    public $template;

    /**
     * AuthenticationPlugin constructor.
     */
    public function __construct()
    {
        $this->ipAllowDeny = new IpAllowDeny();
        $this->template = new Template();
    }

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
        global $cfg;

        $this->setSessionAccessTime();

        $cfg['Server']['user']     = $this->user;
        $cfg['Server']['password'] = $this->password;

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
     * @param string $failure String describing why authentication has failed
     *
     * @return void
     */
    public function showFailure($failure)
    {
        Logging::logUser($this->user, $failure);
    }

    /**
     * Perform logout
     *
     * @return void
     */
    public function logOut()
    {
        /** @var Config $PMA_Config */
        global $PMA_Config;

        /* Obtain redirect URL (before doing logout) */
        if (! empty($GLOBALS['cfg']['Server']['LogoutURL'])) {
            $redirect_url = $GLOBALS['cfg']['Server']['LogoutURL'];
        } else {
            $redirect_url = $this->getLoginFormURL();
        }

        /* Clear credentials */
        $this->user = '';
        $this->password = '';

        /*
         * Get a logged-in server count in case of LoginCookieDeleteAll is disabled.
         */
        $server = 0;
        if ($GLOBALS['cfg']['LoginCookieDeleteAll'] === false
            && $GLOBALS['cfg']['Server']['auth_type'] == 'cookie'
        ) {
            foreach ($GLOBALS['cfg']['Servers'] as $key => $val) {
                if ($PMA_Config->issetCookie('pmaAuth-' . $key)) {
                    $server = $key;
                }
            }
        }

        if ($server === 0) {
            /* delete user's choices that were stored in session */
            if (! defined('TESTSUITE')) {
                session_unset();
                session_destroy();
            }

            /* Redirect to login form (or configured URL) */
            Core::sendHeaderLocation($redirect_url);
        } else {
            /* Redirect to other autenticated server */
            $_SESSION['partial_logout'] = true;
            Core::sendHeaderLocation(
                './index.php' . Url::getCommonRaw(['server' => $server])
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
     * @param string $failure String describing why authentication has failed
     *
     * @return string
     */
    public function getErrorMessage($failure)
    {
        if ($failure == 'empty-denied') {
            return __(
                'Login without a password is forbidden by configuration'
                . ' (see AllowNoPassword)'
            );
        } elseif ($failure == 'root-denied' || $failure == 'allow-denied') {
            return __('Access denied!');
        } elseif ($failure == 'no-activity') {
            return sprintf(
                __('No activity within %s seconds; please log in again.'),
                intval($GLOBALS['cfg']['LoginCookieValidity'])
            );
        }

        $dbi_error = $GLOBALS['dbi']->getError();
        if (! empty($dbi_error)) {
            return htmlspecialchars($dbi_error);
        } elseif (isset($GLOBALS['errno'])) {
            return '#' . $GLOBALS['errno'] . ' '
            . __('Cannot log in to the MySQL server');
        }

        return __('Cannot log in to the MySQL server');
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
            $guid = (string) $_REQUEST['guid'];
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
        $success = $this->readCredentials();

        /* Show login form (this exits) */
        if (! $success) {
            /* Force generating of new session */
            Session::secure();
            $this->showLoginForm();
        }

        /* Store credentials (eg. in cookies) */
        $this->storeCredentials();
        /* Check allow/deny rules */
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
                if ($this->ipAllowDeny->allow()) {
                    $allowDeny_forbidden = false;
                }
                if ($this->ipAllowDeny->deny()) {
                    $allowDeny_forbidden = true;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] == 'deny,allow') {
                if ($this->ipAllowDeny->deny()) {
                    $allowDeny_forbidden = true;
                }
                if ($this->ipAllowDeny->allow()) {
                    $allowDeny_forbidden = false;
                }
            } elseif ($cfg['Server']['AllowDeny']['order'] == 'explicit') {
                if ($this->ipAllowDeny->allow() && ! $this->ipAllowDeny->deny()) {
                    $allowDeny_forbidden = false;
                } else {
                    $allowDeny_forbidden = true;
                }
            } // end if ... elseif ... elseif

            // Ejects the user if banished
            if ($allowDeny_forbidden) {
                $this->showFailure('allow-denied');
            }
        } // end if

        // is root allowed?
        if (! $cfg['Server']['AllowRoot'] && $cfg['Server']['user'] == 'root') {
            $this->showFailure('root-denied');
        }

        // is a login without password allowed?
        if (! $cfg['Server']['AllowNoPassword']
            && $cfg['Server']['password'] === ''
        ) {
            $this->showFailure('empty-denied');
        }
    }

    /**
     * Checks whether two factor authentication is active
     * for given user and performs it.
     *
     * @return boolean|void
     */
    public function checkTwoFactor()
    {
        $twofactor = new TwoFactor($this->user);

        /* Do we need to show the form? */
        if ($twofactor->check()) {
            return;
        }

        $response = Response::getInstance();
        if ($response->loginPage()) {
            if (defined('TESTSUITE')) {
                return;
            } else {
                exit;
            }
        }
        echo $this->template->render('login/header', ['theme' => $GLOBALS['PMA_Theme']]);
        Message::rawNotice(
            __('You have enabled two factor authentication, please confirm your login.')
        )->display();
        echo $this->template->render('login/twofactor', [
            'form' => $twofactor->render(),
            'show_submit' => $twofactor->showSubmit,
        ]);
        echo $this->template->render('login/footer');
        echo Config::renderFooter();
        if (! defined('TESTSUITE')) {
            exit;
        }
    }
}
