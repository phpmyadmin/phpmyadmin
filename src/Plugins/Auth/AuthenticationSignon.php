<?php
/**
 * SignOn Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function defined;
use function file_exists;
use function in_array;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_set_cookie_params;
use function session_start;
use function session_write_close;

/**
 * Handles the SignOn authentication method
 */
class AuthenticationSignon extends AuthenticationPlugin
{
    /**
     * Displays authentication form
     */
    public function showLoginForm(): never
    {
        $response = ResponseRenderer::getInstance();
        $response->disable();
        unset($_SESSION['LAST_SIGNON_URL']);
        $config = Config::getInstance();
        if (empty($config->selectedServer['SignonURL'])) {
            echo $this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => 'You must set SignonURL!',
            ]);

            $response->callExit();
        } else {
            $response->redirect($config->selectedServer['SignonURL']);
        }

        $response->callExit();
    }

    /**
     * Set cookie params
     *
     * @param mixed[] $sessionCookieParams The cookie params
     */
    public function setCookieParams(array|null $sessionCookieParams = null): void
    {
        /* Session cookie params from config */
        $sessionCookieParams ??= Config::getInstance()->selectedServer['SignonCookieParams'];

        foreach (['lifetime', 'path', 'domain', 'secure', 'httponly'] as $key) {
            if (isset($sessionCookieParams[$key])) {
                continue;
            }

            $sessionCookieParams[$key] = match ($key) {
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure', 'httponly' => false,
            };
        }

        if (
            isset($sessionCookieParams['samesite'])
            && ! in_array($sessionCookieParams['samesite'], ['Lax', 'Strict'], true)
        ) {
            // Not a valid value for samesite
            unset($sessionCookieParams['samesite']);
        }

        session_set_cookie_params($sessionCookieParams);
    }

    /**
     * Gets authentication credentials
     */
    public function readCredentials(): bool
    {
        $config = Config::getInstance();
        /* Check if we're using same signon server */
        $signonUrl = $config->selectedServer['SignonURL'];
        if (isset($_SESSION['LAST_SIGNON_URL']) && $_SESSION['LAST_SIGNON_URL'] != $signonUrl) {
            return false;
        }

        /* Script name */
        $scriptName = $config->selectedServer['SignonScript'];

        /* Session name */
        $sessionName = $config->selectedServer['SignonSession'];

        /* Current host */
        $singleSignonHost = $config->selectedServer['host'];

        /* Current port */
        $singleSignonPort = $config->selectedServer['port'];

        /* No configuration updates */
        $singleSignonCfgUpdate = [];

        /* Handle script based auth */
        if ($scriptName !== '') {
            if (! @file_exists($scriptName)) {
                echo $this->template->render('error/generic', [
                    'lang' => $GLOBALS['lang'] ?? 'en',
                    'dir' => LanguageManager::$textDir,
                    'error_message' => __('Can not find signon authentication script:') . ' ' . $scriptName,
                ]);

                ResponseRenderer::getInstance()->callExit();
            }

            include $scriptName;

            [$this->user, $this->password] = get_login_credentials($config->selectedServer['user']);
        } elseif (isset($_COOKIE[$sessionName])) { /* Does session exist? */
            /* End current session */
            $oldSession = session_name();
            $oldId = session_id();
            $oldCookieParams = session_get_cookie_params();
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Load single signon session */
            if (! defined('TESTSUITE')) {
                $this->setCookieParams();
                session_name($sessionName);
                session_id($_COOKIE[$sessionName]);
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
                $singleSignonHost = $_SESSION['PMA_single_signon_host'];
            }

            if (isset($_SESSION['PMA_single_signon_port'])) {
                $singleSignonPort = $_SESSION['PMA_single_signon_port'];
            }

            if (isset($_SESSION['PMA_single_signon_cfgupdate'])) {
                $singleSignonCfgUpdate = $_SESSION['PMA_single_signon_cfgupdate'];
            }

            /* Also get token as it is needed to access subpages */
            if (isset($_SESSION['PMA_single_signon_token'])) {
                /* No need to care about token on logout */
                $pmaToken = $_SESSION['PMA_single_signon_token'];
            }

            $hmacSecret = $_SESSION['PMA_single_signon_HMAC_secret'] ?? Util::generateRandom(16);

            /* End single signon session */
            if (! defined('TESTSUITE')) {
                session_write_close();
            }

            /* Restart phpMyAdmin session */
            if (! defined('TESTSUITE')) {
                $this->setCookieParams($oldCookieParams);
                if ($oldSession !== false) {
                    session_name($oldSession);
                }

                if ($oldId !== '' && $oldId !== false) {
                    session_id($oldId);
                }

                session_start();
            }

            /* Set the single signon host */
            $config->selectedServer['host'] = $singleSignonHost;

            /* Set the single signon port */
            $config->selectedServer['port'] = $singleSignonPort;

            /* Configuration update */
            $config->selectedServer = array_merge($config->selectedServer, $singleSignonCfgUpdate);

            /* Restore our token */
            if (! empty($pmaToken)) {
                $_SESSION[' PMA_token '] = $pmaToken;
                $_SESSION[' HMAC_secret '] = $hmacSecret;
            }

            /**
             * Clear user cache.
             */
            Util::clearUserCache();
        }

        // Returns whether we get authentication settings or not
        if ($this->user === '') {
            unset($_SESSION['LAST_SIGNON_URL']);

            return false;
        }

        $_SESSION['LAST_SIGNON_URL'] = $config->selectedServer['SignonURL'];

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * @param string $failure String describing why authentication has failed
     */
    public function showFailure(string $failure): never
    {
        parent::showFailure($failure);

        /* Session name */
        $sessionName = Config::getInstance()->selectedServer['SignonSession'];

        /* Does session exist? */
        if (isset($_COOKIE[$sessionName])) {
            if (! defined('TESTSUITE')) {
                /* End current session */
                session_write_close();

                /* Load single signon session */
                $this->setCookieParams();
                session_name($sessionName);
                session_id($_COOKIE[$sessionName]);
                session_start();
            }

            /* Set error message */
            $_SESSION['PMA_single_signon_error_message'] = $this->getErrorMessage($failure);
        }

        $this->showLoginForm();
    }

    /**
     * Returns URL for login form.
     */
    public function getLoginFormURL(): string
    {
        return Config::getInstance()->selectedServer['SignonURL'];
    }
}
