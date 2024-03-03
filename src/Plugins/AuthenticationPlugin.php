<?php
/**
 * Abstract class for the authentication plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Exceptions\SessionHandlerException;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\IpAllowDeny;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Session;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_keys;
use function defined;
use function htmlspecialchars;
use function max;
use function min;
use function session_destroy;
use function session_unset;
use function sprintf;
use function time;

/**
 * Provides a common interface that will have to be implemented by all of the
 * authentication plugins.
 */
abstract class AuthenticationPlugin
{
    /**
     * Username
     */
    public string $user = '';

    /**
     * Password
     */
    public string $password = '';

    protected IpAllowDeny $ipAllowDeny;

    public Template $template;

    public function __construct()
    {
        $this->ipAllowDeny = new IpAllowDeny();
        $this->template = new Template();
    }

    /**
     * Displays authentication form
     */
    abstract public function showLoginForm(): void;

    /**
     * Gets authentication credentials
     */
    abstract public function readCredentials(): bool;

    /**
     * Set the user and password after last checkings if required
     */
    public function storeCredentials(): bool
    {
        $this->setSessionAccessTime();

        $config = Config::getInstance();
        $config->selectedServer['user'] = $this->user;
        $config->selectedServer['password'] = $this->password;

        return true;
    }

    /**
     * Stores user credentials after successful login.
     */
    public function rememberCredentials(): void
    {
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure(string $failure): void
    {
        Logging::logUser(Config::getInstance(), $this->user, $failure);
    }

    /**
     * Perform logout
     */
    public function logOut(): void
    {
        $config = Config::getInstance();
        /* Obtain redirect URL (before doing logout) */
        if (! empty($config->selectedServer['LogoutURL'])) {
            $redirectUrl = $config->selectedServer['LogoutURL'];
        } else {
            $redirectUrl = $this->getLoginFormURL();
        }

        /* Clear credentials */
        $this->user = '';
        $this->password = '';

        // Get a logged-in server count in case of LoginCookieDeleteAll is disabled.
        $server = 0;
        if ($config->settings['LoginCookieDeleteAll'] === false && $config->selectedServer['auth_type'] === 'cookie') {
            foreach (array_keys($config->settings['Servers']) as $key) {
                if (! $config->issetCookie('pmaAuth-' . $key)) {
                    continue;
                }

                $server = $key;
            }
        }

        $response = ResponseRenderer::getInstance();
        if ($server === 0) {
            /* delete user's choices that were stored in session */
            if (! defined('TESTSUITE')) {
                session_unset();
                session_destroy();
            }

            /* Redirect to login form (or configured URL) */
            $response->redirect($redirectUrl);
        } else {
            /* Redirect to other authenticated server */
            $_SESSION['partial_logout'] = true;
            $response->redirect('./index.php?route=/' . Url::getCommonRaw(['server' => $server], '&'));
        }
    }

    /**
     * Returns URL for login form.
     */
    public function getLoginFormURL(): string
    {
        return './index.php?route=/';
    }

    /**
     * Returns error message for failed authentication.
     *
     * @param string $failure String describing why authentication has failed
     */
    public function getErrorMessage(string $failure): string
    {
        if ($failure === 'empty-denied') {
            return __('Login without a password is forbidden by configuration (see AllowNoPassword)');
        }

        if ($failure === 'root-denied' || $failure === 'allow-denied') {
            return __('Access denied!');
        }

        if ($failure === 'no-activity') {
            return sprintf(
                __('You have been automatically logged out due to inactivity of %s seconds.'
                . ' Once you log in again, you should be able to resume the work where you left off.'),
                (int) Config::getInstance()->settings['LoginCookieValidity'],
            );
        }

        $dbiError = DatabaseInterface::getInstance()->getError();
        if ($dbiError !== '') {
            return htmlspecialchars($dbiError);
        }

        if (isset($GLOBALS['errno'])) {
            return '#' . $GLOBALS['errno'] . ' '
            . __('Cannot log in to the MySQL server');
        }

        return __('Cannot log in to the MySQL server');
    }

    /**
     * Callback when user changes password.
     *
     * @param string $password New password to set
     */
    public function handlePasswordChange(string $password): void
    {
    }

    /**
     * Store session access time in session.
     *
     * Tries to workaround PHP 5 session garbage collection which
     * looks at the session file's last modified time
     */
    public function setSessionAccessTime(): void
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
            $time = time() - min(
                max(0, (int) $_REQUEST['access_time']),
                Config::getInstance()->settings['LoginCookieValidity'] + 1,
            );
        } else {
            $time = time();
        }

        $_SESSION['browser_access_time'][$guid] = $time;
    }

    /**
     * High level authentication interface
     *
     * Gets the credentials or shows login form if necessary
     */
    public function authenticate(): void
    {
        $success = $this->readCredentials();

        /* Show login form (this exits) */
        if (! $success) {
            /* Force generating of new session */
            try {
                Session::secure();
            } catch (SessionHandlerException $exception) {
                $responseRenderer = ResponseRenderer::getInstance();
                $responseRenderer->addHTML((new Template())->render('error/generic', [
                    'lang' => $GLOBALS['lang'] ?? 'en',
                    'dir' => LanguageManager::$textDir,
                    'error_message' => $exception->getMessage(),
                ]));

                $responseRenderer->callExit();
            }

            $this->showLoginForm();
        }

        /* Store credentials (eg. in cookies) */
        $this->storeCredentials();
        /* Check allow/deny rules */
        $this->checkRules();
        /* clear user cache */
        Util::clearUserCache();
    }

    /**
     * Check configuration defined restrictions for authentication
     */
    public function checkRules(): void
    {
        $config = Config::getInstance();
        // Check IP-based Allow/Deny rules as soon as possible to reject the
        // user based on mod_access in Apache
        if (isset($config->selectedServer['AllowDeny']['order'])) {
            $allowDenyForbidden = false; // default
            if ($config->selectedServer['AllowDeny']['order'] === 'allow,deny') {
                $allowDenyForbidden = ! ($this->ipAllowDeny->allow() && ! $this->ipAllowDeny->deny());
            } elseif ($config->selectedServer['AllowDeny']['order'] === 'deny,allow') {
                $allowDenyForbidden = $this->ipAllowDeny->deny() && ! $this->ipAllowDeny->allow();
            } elseif ($config->selectedServer['AllowDeny']['order'] === 'explicit') {
                $allowDenyForbidden = ! ($this->ipAllowDeny->allow() && ! $this->ipAllowDeny->deny());
            }

            // Ejects the user if banished
            if ($allowDenyForbidden) {
                $this->showFailure('allow-denied');
            }
        }

        // is root allowed?
        if (! $config->selectedServer['AllowRoot'] && $config->selectedServer['user'] === 'root') {
            $this->showFailure('root-denied');
        }

        // is a login without password allowed?
        if ($config->selectedServer['AllowNoPassword'] || $config->selectedServer['password'] !== '') {
            return;
        }

        $this->showFailure('empty-denied');
    }

    /**
     * Checks whether two factor authentication is active
     * for given user and performs it.
     *
     * @throws ExitException
     */
    public function checkTwoFactor(ServerRequest $request): void
    {
        $twofactor = new TwoFactor($this->user);

        /* Do we need to show the form? */
        if ($twofactor->check($request)) {
            return;
        }

        $response = ResponseRenderer::getInstance();
        if ($response->loginPage()) {
            $response->callExit();
        }

        $response->addHTML($this->template->render('login/header', ['session_expired' => false]));
        $response->addHTML(Message::rawNotice(
            __('You have enabled two factor authentication, please confirm your login.'),
        )->getDisplay());
        $response->addHTML($this->template->render('login/twofactor', [
            'form' => $twofactor->render($request),
            'show_submit' => $twofactor->showSubmit(),
        ]));
        $response->addHTML($this->template->render('login/footer'));
        $response->addHTML(Config::renderFooter());
        $response->callExit();
    }
}
