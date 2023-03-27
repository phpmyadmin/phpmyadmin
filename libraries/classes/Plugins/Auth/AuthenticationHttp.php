<?php
/**
 * HTTP Authentication plugin for phpMyAdmin.
 * NOTE: Requires PHP loaded as a Apache module.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function base64_decode;
use function defined;
use function hash_equals;
use function preg_replace;
use function sprintf;
use function strcmp;
use function strpos;
use function substr;

/**
 * Handles the HTTP authentication methods
 */
class AuthenticationHttp extends AuthenticationPlugin
{
    /**
     * Displays authentication form and redirect as necessary
     *
     * @return bool always true (no return indeed)
     */
    public function showLoginForm(): bool
    {
        $response = ResponseRenderer::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            // reload_flag removes the token parameter from the URL and reloads
            $response->addJSON('reload_flag', '1');
            if (defined('TESTSUITE')) {
                return true;
            }

            exit;
        }

        return $this->authForm();
    }

    /**
     * Displays authentication form
     */
    public function authForm(): bool
    {
        if (empty($GLOBALS['cfg']['Server']['auth_http_realm'])) {
            if (empty($GLOBALS['cfg']['Server']['verbose'])) {
                $serverMessage = $GLOBALS['cfg']['Server']['host'];
            } else {
                $serverMessage = $GLOBALS['cfg']['Server']['verbose'];
            }

            $realmMessage = 'phpMyAdmin ' . $serverMessage;
        } else {
            $realmMessage = $GLOBALS['cfg']['Server']['auth_http_realm'];
        }

        $response = ResponseRenderer::getInstance();

        // remove non US-ASCII to respect RFC2616
        $realmMessage = preg_replace('/[^\x20-\x7e]/i', '', $realmMessage);
        $response->header('WWW-Authenticate: Basic realm="' . $realmMessage . '"');
        $response->setHttpResponseCode(401);

        /* HTML header */
        $response->setMinimalFooter();
        $header = $response->getHeader();
        $header->setTitle(__('Access denied!'));
        $header->disableMenuAndConsole();
        $header->setBodyId('loginform');

        $response->addHTML('<h1>');
        $response->addHTML(sprintf(__('Welcome to %s'), ' phpMyAdmin'));
        $response->addHTML('</h1>');
        $response->addHTML('<h3>');
        $response->addHTML(
            Message::error(
                __('Wrong username/password. Access denied.'),
            )->getDisplay(),
        );
        $response->addHTML('</h3>');

        $response->addHTML(Config::renderFooter());

        if (! defined('TESTSUITE')) {
            exit;
        }

        return false;
    }

    /**
     * Gets authentication credentials
     */
    public function readCredentials(): bool
    {
        // Grabs the $PHP_AUTH_USER variable
        if (isset($GLOBALS['PHP_AUTH_USER'])) {
            $this->user = $GLOBALS['PHP_AUTH_USER'];
        }

        if (empty($this->user)) {
            if (Core::getenv('PHP_AUTH_USER')) {
                $this->user = Core::getenv('PHP_AUTH_USER');
            } elseif (Core::getenv('REMOTE_USER')) {
                // CGI, might be encoded, see below
                $this->user = Core::getenv('REMOTE_USER');
            } elseif (Core::getenv('REDIRECT_REMOTE_USER')) {
                // CGI, might be encoded, see below
                $this->user = Core::getenv('REDIRECT_REMOTE_USER');
            } elseif (Core::getenv('AUTH_USER')) {
                // WebSite Professional
                $this->user = Core::getenv('AUTH_USER');
            } elseif (Core::getenv('HTTP_AUTHORIZATION')) {
                // IIS, might be encoded, see below
                $this->user = Core::getenv('HTTP_AUTHORIZATION');
            } elseif (Core::getenv('Authorization')) {
                // FastCGI, might be encoded, see below
                $this->user = Core::getenv('Authorization');
            }
        }

        // Grabs the $PHP_AUTH_PW variable
        if (isset($GLOBALS['PHP_AUTH_PW'])) {
            $this->password = $GLOBALS['PHP_AUTH_PW'];
        }

        if ($this->password === '') {
            if (Core::getenv('PHP_AUTH_PW')) {
                $this->password = Core::getenv('PHP_AUTH_PW');
            } elseif (Core::getenv('REMOTE_PASSWORD')) {
                // Apache/CGI
                $this->password = Core::getenv('REMOTE_PASSWORD');
            } elseif (Core::getenv('AUTH_PASSWORD')) {
                // WebSite Professional
                $this->password = Core::getenv('AUTH_PASSWORD');
            }
        }

        // Avoid showing the password in phpinfo()'s output
        unset($GLOBALS['PHP_AUTH_PW'], $_SERVER['PHP_AUTH_PW']);

        // Decode possibly encoded information (used by IIS/CGI/FastCGI)
        // (do not use explode() because a user might have a colon in their password
        if (strcmp(substr($this->user, 0, 6), 'Basic ') == 0) {
            $userPass = base64_decode(substr($this->user, 6));
            if (! empty($userPass)) {
                $colon = strpos($userPass, ':');
                if ($colon) {
                    $this->user = substr($userPass, 0, $colon);
                    $this->password = substr($userPass, $colon + 1);
                }

                unset($colon);
            }

            unset($userPass);
        }

        // sanitize username
        $this->user = Core::sanitizeMySQLUser($this->user);

        // User logged out -> ensure the new username is not the same
        $oldUser = $_REQUEST['old_usr'] ?? '';
        if (! empty($oldUser) && hash_equals($oldUser, $this->user)) {
            $this->user = '';
        }

        // Returns whether we get authentication settings or not
        return $this->user !== '';
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure(string $failure): void
    {
        parent::showFailure($failure);

        $error = $GLOBALS['dbi']->getError();
        if ($error && $GLOBALS['errno'] != 1045) {
            echo $this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => $GLOBALS['text_dir'] ?? 'ltr',
                'error_message' => $error,
            ]);

            if (! defined('TESTSUITE')) {
                exit;
            }
        }

        $this->authForm();
    }

    /**
     * Returns URL for login form.
     */
    public function getLoginFormURL(): string
    {
        return './index.php?route=/&old_usr=' . $this->user;
    }
}
