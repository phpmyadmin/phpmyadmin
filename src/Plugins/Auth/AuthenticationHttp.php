<?php
/**
 * HTTP Authentication plugin for phpMyAdmin.
 * NOTE: Requires PHP loaded as a Apache module.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function base64_decode;
use function hash_equals;
use function preg_replace;
use function sprintf;
use function str_starts_with;
use function strpos;
use function substr;

/**
 * Handles the HTTP authentication methods
 */
class AuthenticationHttp extends AuthenticationPlugin
{
    /**
     * Displays authentication form and redirect as necessary
     */
    public function showLoginForm(): never
    {
        $response = ResponseRenderer::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            // reload_flag removes the token parameter from the URL and reloads
            $response->addJSON('reload_flag', '1');
            $response->callExit();
        }

        $this->authForm();
    }

    /**
     * Displays authentication form
     */
    public function authForm(): never
    {
        $config = Config::getInstance();
        if (empty($config->selectedServer['auth_http_realm'])) {
            if (empty($config->selectedServer['verbose'])) {
                $serverMessage = $config->selectedServer['host'];
            } else {
                $serverMessage = $config->selectedServer['verbose'];
            }

            $realmMessage = 'phpMyAdmin ' . $serverMessage;
        } else {
            $realmMessage = $config->selectedServer['auth_http_realm'];
        }

        $response = ResponseRenderer::getInstance();

        // remove non US-ASCII to respect RFC2616
        $realmMessage = preg_replace('/[^\x20-\x7e]/i', '', $realmMessage);
        $response->addHeader('WWW-Authenticate', 'Basic realm="' . $realmMessage . '"');
        $response->setStatusCode(StatusCodeInterface::STATUS_UNAUTHORIZED);

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

        $response->callExit();
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

        if ($this->user === '') {
            if (Core::getEnv('PHP_AUTH_USER') !== '') {
                $this->user = Core::getEnv('PHP_AUTH_USER');
            } elseif (Core::getEnv('REMOTE_USER') !== '') {
                // CGI, might be encoded, see below
                $this->user = Core::getEnv('REMOTE_USER');
            } elseif (Core::getEnv('REDIRECT_REMOTE_USER') !== '') {
                // CGI, might be encoded, see below
                $this->user = Core::getEnv('REDIRECT_REMOTE_USER');
            } elseif (Core::getEnv('AUTH_USER') !== '') {
                // WebSite Professional
                $this->user = Core::getEnv('AUTH_USER');
            } elseif (Core::getEnv('HTTP_AUTHORIZATION') !== '') {
                // IIS, might be encoded, see below
                $this->user = Core::getEnv('HTTP_AUTHORIZATION');
            } elseif (Core::getEnv('Authorization') !== '') {
                // FastCGI, might be encoded, see below
                $this->user = Core::getEnv('Authorization');
            }
        }

        // Grabs the $PHP_AUTH_PW variable
        if (isset($GLOBALS['PHP_AUTH_PW'])) {
            $this->password = $GLOBALS['PHP_AUTH_PW'];
        }

        if ($this->password === '') {
            if (Core::getEnv('PHP_AUTH_PW') !== '') {
                $this->password = Core::getEnv('PHP_AUTH_PW');
            } elseif (Core::getEnv('REMOTE_PASSWORD') !== '') {
                // Apache/CGI
                $this->password = Core::getEnv('REMOTE_PASSWORD');
            } elseif (Core::getEnv('AUTH_PASSWORD') !== '') {
                // WebSite Professional
                $this->password = Core::getEnv('AUTH_PASSWORD');
            }
        }

        // Avoid showing the password in phpinfo()'s output
        unset($GLOBALS['PHP_AUTH_PW'], $_SERVER['PHP_AUTH_PW']);

        // Decode possibly encoded information (used by IIS/CGI/FastCGI)
        // (do not use explode() because a user might have a colon in their password
        if (str_starts_with($this->user, 'Basic ')) {
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
    public function showFailure(string $failure): never
    {
        parent::showFailure($failure);

        $error = DatabaseInterface::getInstance()->getError();
        if ($error && $GLOBALS['errno'] != 1045) {
            echo $this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => $error,
            ]);

            ResponseRenderer::getInstance()->callExit();
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
