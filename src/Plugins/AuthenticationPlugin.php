<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use Exception;
use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\IpAllowDeny;
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
 * Provides a common interface that will have to be implemented by all the authentication plugins.
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
    abstract public function showLoginForm(): Response|null;

    /**
     * Gets authentication credentials
     *
     * @throws AuthenticationFailure
     * @throws Exception
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
    public function rememberCredentials(): Response|null
    {
        return null;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     */
    abstract public function showFailure(AuthenticationFailure $failure): Response;

    protected function logFailure(AuthenticationFailure $failure): void
    {
        Logging::logUser(Config::getInstance(), $this->user, $failure->failureType);
    }

    /**
     * Perform logout
     */
    public function logOut(): Response
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

            return $response->response();
        }

        /* Redirect to other authenticated server */
        $_SESSION['partial_logout'] = true;
        $response->redirect('./index.php?route=/' . Url::getCommonRaw(['server' => $server], '&'));

        return $response->response();
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
     */
    public function getErrorMessage(AuthenticationFailure $failure): string
    {
        if ($failure->failureType === AuthenticationFailure::NO_ACTIVITY) {
            return sprintf($failure->getMessage(), Config::getInstance()->config->LoginCookieValidity);
        }

        if ($failure->failureType === AuthenticationFailure::SERVER_DENIED) {
            $dbiError = DatabaseInterface::getInstance()->getError();
            if ($dbiError !== '') {
                return htmlspecialchars($dbiError);
            }

            if (DatabaseInterface::$errorNumber !== null) {
                return '#' . DatabaseInterface::$errorNumber . ' ' . $failure->getMessage();
            }
        }

        return $failure->getMessage();
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
                Config::getInstance()->config->LoginCookieValidity + 1,
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
     *
     * @throws AuthenticationFailure
     * @throws Exception
     */
    public function authenticate(): Response|null
    {
        $success = $this->readCredentials();

        /* Show login form (this exits) */
        if (! $success) {
            /* Force generating of new session */
            Session::secure();

            $response = $this->showLoginForm();
            if ($response !== null) {
                return $response;
            }
        }

        /* Store credentials (eg. in cookies) */
        $this->storeCredentials();
        /* Check allow/deny rules */
        $this->checkRules();
        /* clear user cache */
        Util::clearUserCache();

        return null;
    }

    /**
     * Check configuration defined restrictions for authentication
     *
     * @throws AuthenticationFailure
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
                throw AuthenticationFailure::deniedByAllowDenyRules();
            }
        }

        // is root allowed?
        if (! $config->selectedServer['AllowRoot'] && $config->selectedServer['user'] === 'root') {
            throw AuthenticationFailure::rootDeniedByConfiguration();
        }

        // is a login without password allowed?
        if ($config->selectedServer['AllowNoPassword'] || $config->selectedServer['password'] !== '') {
            return;
        }

        throw AuthenticationFailure::emptyPasswordDeniedByConfiguration();
    }

    /**
     * Checks whether two-factor authentication is active for given user and performs it.
     */
    public function checkTwoFactor(ServerRequest $request): Response|null
    {
        $twofactor = new TwoFactor($this->user);

        /* Do we need to show the form? */
        if ($twofactor->check($request)) {
            return null;
        }

        $responseRenderer = ResponseRenderer::getInstance();
        if ($responseRenderer->loginPage()) {
            return $responseRenderer->response();
        }

        $responseRenderer->addHTML($this->template->render('login/header', ['session_expired' => false]));
        $responseRenderer->addHTML(Message::rawNotice(
            __('You have enabled two factor authentication, please confirm your login.'),
        )->getDisplay());
        $responseRenderer->addHTML($this->template->render('login/twofactor', [
            'form' => $twofactor->render($request),
            'show_submit' => $twofactor->showSubmit(),
        ]));
        $responseRenderer->addHTML($this->template->render('login/footer'));
        $responseRenderer->addHTML(Footer::renderFooter());

        return $responseRenderer->response();
    }
}
