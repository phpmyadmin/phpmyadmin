<?php
/**
 * Cookie Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Exceptions\SessionHandlerException;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Session;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;
use ReCaptcha;
use Throwable;

use function __;
use function array_keys;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function function_exists;
use function in_array;
use function ini_get;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function random_bytes;
use function session_id;
use function sodium_crypto_secretbox;
use function sodium_crypto_secretbox_open;
use function strlen;
use function time;

use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
use const SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

/**
 * Handles the cookie authentication method
 */
class AuthenticationCookie extends AuthenticationPlugin
{
    public static string $connectionError = '';
    /** The user provided server to connect to */
    public static string $authServer = '';
    public static bool $fromCookie = false;

    /**
     * Displays authentication form
     *
     * this function MUST exit/quit the application
     */
    public function showLoginForm(): Response
    {
        $responseRenderer = ResponseRenderer::getInstance();

        /**
         * When sending login modal after session has expired, send the
         * new token explicitly with the response to update the token
         * in all the forms having a hidden token.
         */
        $sessionExpired = isset($_REQUEST['check_timeout']) || isset($_REQUEST['session_timedout']);
        if (! $sessionExpired && $responseRenderer->loginPage()) {
            return $responseRenderer->response();
        }

        /**
         * When sending login modal after session has expired, send the
         * new token explicitly with the response to update the token
         * in all the forms having a hidden token.
         */
        if ($sessionExpired) {
            $responseRenderer->setRequestStatus(false);
            $responseRenderer->addJSON('new_token', $_SESSION[' PMA_token ']);
        }

        /**
         * logged_in response parameter is used to check if the login,
         * using the modal was successful after session expiration.
         */
        if (isset($_REQUEST['session_timedout'])) {
            $responseRenderer->addJSON('logged_in', 0);
        }

        $config = Config::getInstance();
        // No recall if blowfish secret is not configured as it would produce
        // garbage
        if ($config->config->LoginCookieRecall && $config->config->blowfish_secret !== '') {
            $defaultUser = $this->user;
            $defaultServer = self::$authServer;
            $hasAutocomplete = true;
        } else {
            $defaultUser = '';
            $defaultServer = '';
            $hasAutocomplete = false;
        }

        // wrap the login form in a div which overlays the whole page.
        $loginHeader = $this->template->render('login/header', ['session_expired' => $sessionExpired]);

        $errorMessages = '';
        // Show error message
        if (self::$connectionError !== '') {
            $errorMessages = Message::rawError(self::$connectionError)->getDisplay();
        } elseif (isset($_GET['session_expired']) && (int) $_GET['session_expired'] === 1) {
            $errorMessages = Message::rawError(
                __('Your session has expired. Please log in again.'),
            )->getDisplay();
        }

        $languageManager = LanguageManager::getInstance();
        $availableLanguages = [];
        if ($config->config->Lang === '' && $languageManager->hasChoice()) {
            $availableLanguages = $languageManager->sortedLanguages();
        }

        $serversOptions = '';
        $hasServers = count($config->settings['Servers']) > 1;
        if ($hasServers) {
            $serversOptions = Select::render(false);
        }

        $formParams = [];
        if (Current::$database !== '') {
            $formParams['db'] = Current::$database;
        }

        if (Current::$table !== '') {
            $formParams['table'] = Current::$table;
        }

        $errors = '';
        $errorHandler = ErrorHandler::getInstance();
        if ($errorHandler->hasDisplayErrors()) {
            $errors = $errorHandler->getDispErrors();
        }

        // close the wrapping div tag, if the request is after session timeout
        if ($sessionExpired) {
            $loginFooter = $this->template->render('login/footer', ['session_expired' => 1]);
        } else {
            $loginFooter = $this->template->render('login/footer', ['session_expired' => 0]);
        }

        $configFooter = Footer::renderFooter();

        $responseRenderer->addHTML($this->template->render('login/form', [
            'login_header' => $loginHeader,
            'is_demo' => $config->config->debug->demo,
            'error_messages' => $errorMessages,
            'available_languages' => $availableLanguages,
            'is_session_expired' => $sessionExpired,
            'has_autocomplete' => $hasAutocomplete,
            'session_id' => session_id(),
            'is_arbitrary_server_allowed' => $config->config->AllowArbitraryServer,
            'default_server' => $defaultServer,
            'default_user' => $defaultUser,
            'has_servers' => $hasServers,
            'server_options' => $serversOptions,
            'server' => Current::$server,
            'lang' => Current::$lang,
            'has_captcha' => $config->config->CaptchaApi !== ''
                && $config->config->CaptchaRequestParam !== ''
                && $config->config->CaptchaResponseParam !== ''
                && $config->config->CaptchaLoginPrivateKey !== ''
                && $config->config->CaptchaLoginPublicKey !== '',
            'use_captcha_checkbox' => $config->config->CaptchaMethod === 'checkbox',
            'captcha_api' => $config->config->CaptchaApi,
            'captcha_req' => $config->config->CaptchaRequestParam,
            'captcha_resp' => $config->config->CaptchaResponseParam,
            'captcha_key' => $config->config->CaptchaLoginPublicKey,
            'form_params' => $formParams,
            'errors' => $errors,
            'login_footer' => $loginFooter,
            'config_footer' => $configFooter,
        ]));

        return $responseRenderer->response();
    }

    /**
     * Gets authentication credentials
     *
     * this function DOES NOT check authentication - it just checks/provides
     * authentication credentials required to connect to the MySQL server
     * usually with $dbi->connect()
     *
     * it returns false if something is missing - which usually leads to
     * showLoginForm() which displays login form
     *
     * it returns true if all seems ok which usually leads to auth_set_user()
     *
     * it directly switches to showFailure() if user inactivity timeout is reached
     *
     * @throws AuthenticationFailure
     * @throws SessionHandlerException
     */
    public function readCredentials(): bool
    {
        self::$authServer = '';

        $this->user = $this->password = '';
        self::$fromCookie = false;

        $config = Config::getInstance();
        if (isset($_POST['pma_username']) && $_POST['pma_username'] != '') {
            // Verify Captcha if it is required.
            if (
                $config->config->CaptchaApi !== ''
                && $config->config->CaptchaRequestParam !== ''
                && $config->config->CaptchaResponseParam !== ''
                && $config->config->CaptchaLoginPrivateKey !== ''
                && $config->config->CaptchaLoginPublicKey !== ''
            ) {
                if (empty($_POST[$config->config->CaptchaResponseParam])) {
                    self::$connectionError = __('Missing Captcha verification, maybe it has been blocked by adblock?');

                    return false;
                }

                $captchaSiteVerifyURL = $config->config->CaptchaSiteVerifyURL;
                $captchaSiteVerifyURL = $captchaSiteVerifyURL === '' ? null : $captchaSiteVerifyURL;
                if (function_exists('curl_init') && function_exists('curl_exec')) {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $config->config->CaptchaLoginPrivateKey,
                        new ReCaptcha\RequestMethod\CurlPost(null, $captchaSiteVerifyURL),
                    );
                } elseif (ini_get('allow_url_fopen')) {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $config->config->CaptchaLoginPrivateKey,
                        new ReCaptcha\RequestMethod\Post($captchaSiteVerifyURL),
                    );
                } else {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $config->config->CaptchaLoginPrivateKey,
                        new ReCaptcha\RequestMethod\SocketPost(null, $captchaSiteVerifyURL),
                    );
                }

                // verify captcha status.
                $resp = $reCaptcha->verify(
                    $_POST[$config->config->CaptchaResponseParam],
                    Core::getIp(),
                );

                // Check if the captcha entered is valid, if not stop the login.
                if (! $resp->isSuccess()) {
                    $codes = $resp->getErrorCodes();

                    if (in_array('invalid-json', $codes)) {
                        self::$connectionError = __('Failed to connect to the reCAPTCHA service!');
                    } else {
                        self::$connectionError = __('Entered captcha is wrong, try again!');
                    }

                    return false;
                }
            }

            // The user just logged in
            $this->user = Core::sanitizeMySQLUser($_POST['pma_username']);

            $password = $_POST['pma_password'] ?? '';
            if (strlen($password) >= 2000) {
                self::$connectionError = __('Your password is too long. To prevent denial-of-service attacks, ' .
                    'phpMyAdmin restricts passwords to less than 2000 characters.');

                return false;
            }

            $this->password = $password;

            if ($config->config->AllowArbitraryServer && isset($_REQUEST['pma_servername'])) {
                if ($config->config->ArbitraryServerRegexp !== '') {
                    $parts = explode(' ', $_REQUEST['pma_servername']);
                    if (count($parts) === 2) {
                        $tmpHost = $parts[0];
                    } else {
                        $tmpHost = $_REQUEST['pma_servername'];
                    }

                    if (preg_match($config->config->ArbitraryServerRegexp, $tmpHost) !== 1) {
                        self::$connectionError = __('You are not allowed to log in to this MySQL server!');

                        return false;
                    }
                }

                self::$authServer = Core::sanitizeMySQLHost($_REQUEST['pma_servername']);
            }

            /* Secure current session on login to avoid session fixation */
            Session::secure();

            return true;
        }

        // At the end, try to set the $this->user
        // and $this->password variables from cookies

        // check cookies
        $serverCookie = $config->getCookie('pmaUser-' . Current::$server);
        if (empty($serverCookie)) {
            return false;
        }

        $value = $this->cookieDecrypt(
            $serverCookie,
            $this->getEncryptionSecret(),
        );

        if ($value === null) {
            return false;
        }

        $this->user = $value;
        // user was never logged in since session start
        if (empty($_SESSION['browser_access_time'])) {
            return false;
        }

        // User inactive too long
        $lastAccessTime = time() - $config->config->LoginCookieValidity;
        foreach ($_SESSION['browser_access_time'] as $key => $value) {
            if ($value >= $lastAccessTime) {
                continue;
            }

            unset($_SESSION['browser_access_time'][$key]);
        }

        // All sessions expired
        if (empty($_SESSION['browser_access_time'])) {
            SessionCache::remove('is_create_db_priv');
            SessionCache::remove('is_reload_priv');
            SessionCache::remove('db_to_create');
            SessionCache::remove('dbs_to_test');
            SessionCache::remove('db_priv');
            SessionCache::remove('col_priv');
            SessionCache::remove('table_priv');
            SessionCache::remove('proc_priv');

            throw AuthenticationFailure::loggedOutDueToInactivity();
        }

        // check password cookie
        $serverCookie = $config->getCookie('pmaAuth-' . Current::$server);

        if (empty($serverCookie)) {
            return false;
        }

        $value = $this->cookieDecrypt(
            $serverCookie,
            $this->getSessionEncryptionSecret(),
        );
        if ($value === null) {
            return false;
        }

        $authData = json_decode($value, true);

        if (! is_array($authData) || ! isset($authData['password'])) {
            return false;
        }

        $this->password = $authData['password'];
        if ($config->config->AllowArbitraryServer && ! empty($authData['server'])) {
            self::$authServer = $authData['server'];
        }

        self::$fromCookie = true;

        return true;
    }

    /**
     * Set the user and password after last checkings if required
     *
     * @return bool always true
     */
    public function storeCredentials(): bool
    {
        $config = Config::getInstance();
        if ($config->config->AllowArbitraryServer && self::$authServer !== '') {
            /* Allow to specify 'host port' */
            $parts = explode(' ', self::$authServer);
            if (count($parts) === 2) {
                $tmpHost = $parts[0];
                $tmpPort = $parts[1];
            } else {
                $tmpHost = self::$authServer;
                $tmpPort = '';
            }

            if ($config->selectedServer['host'] !== self::$authServer) {
                $config->selectedServer['host'] = $tmpHost;
                if ($tmpPort !== '') {
                    $config->selectedServer['port'] = $tmpPort;
                }
            }

            unset($tmpHost, $tmpPort, $parts);
        }

        return parent::storeCredentials();
    }

    /**
     * Stores user credentials after successful login.
     */
    public function rememberCredentials(): Response|null
    {
        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        $this->storeUsernameCookie($this->user);

        // Duration = as configured
        // Do not store password cookie on password change as we will
        // set the cookie again after password has been changed
        if (! isset($_POST['change_pw'])) {
            $this->storePasswordCookie($this->password);
        }

        // any parameters to pass?
        $urlParams = [];
        if (Current::$database !== '') {
            $urlParams['db'] = Current::$database;
        }

        if (Current::$table !== '') {
            $urlParams['table'] = Current::$table;
        }

        // user logged in successfully after session expiration
        if (isset($_REQUEST['session_timedout'])) {
            $responseRenderer = ResponseRenderer::getInstance();
            $responseRenderer->addJSON('logged_in', 1);
            $responseRenderer->addJSON('success', 1);
            $responseRenderer->addJSON('new_token', $_SESSION[' PMA_token ']);

            return $responseRenderer->response();
        }

        // Set server cookies if required (once per session) and, in this case,
        // force reload to ensure the client accepts cookies
        if (self::$fromCookie) {
            return null;
        }

        /**
         * Clear user cache.
         */
        Util::clearUserCache();

        $responseRenderer = ResponseRenderer::getInstance();

        return ResponseFactory::create()->createResponse(StatusCodeInterface::STATUS_FOUND)->withHeader(
            'Location',
            $responseRenderer->fixRelativeUrlForRedirect('./index.php?route=/' . Url::getCommonRaw($urlParams, '&')),
        );
    }

    /**
     * Stores username in a cookie.
     *
     * @param string $username User name
     */
    public function storeUsernameCookie(string $username): void
    {
        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        Config::getInstance()->setCookie(
            'pmaUser-' . Current::$server,
            $this->cookieEncrypt(
                $username,
                $this->getEncryptionSecret(),
            ),
        );
    }

    /**
     * Stores password in a cookie.
     *
     * @param string $password Password
     */
    public function storePasswordCookie(string $password): void
    {
        $payload = ['password' => $password];
        $config = Config::getInstance();
        if ($config->config->AllowArbitraryServer && self::$authServer !== '') {
            $payload['server'] = self::$authServer;
        }

        // Duration = as configured
        $config->setCookie(
            'pmaAuth-' . Current::$server,
            $this->cookieEncrypt(
                (string) json_encode($payload),
                $this->getSessionEncryptionSecret(),
            ),
            null,
            $config->config->LoginCookieStore,
        );
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * prepares error message and switches to showLoginForm() which display the error
     * and the login form
     */
    public function showFailure(AuthenticationFailure $failure): Response
    {
        $this->logFailure($failure);

        // Deletes password cookie and displays the login form
        Config::getInstance()->removeCookie('pmaAuth-' . Current::$server);

        self::$connectionError = $this->getErrorMessage($failure);

        $responseRenderer = ResponseRenderer::getInstance();

        // needed for PHP-CGI (not need for FastCGI or mod-php)
        $responseRenderer->addHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $responseRenderer->addHeader('Pragma', 'no-cache');

        return $this->showLoginForm();
    }

    /**
     * Returns blowfish secret or generates one if needed.
     */
    private function getEncryptionSecret(): string
    {
        $key = Config::getInstance()->config->blowfish_secret;

        $length = mb_strlen($key, '8bit');
        if ($length === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }

        if ($length > SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return mb_substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, '8bit');
        }

        return $this->getSessionEncryptionSecret();
    }

    /**
     * Returns blowfish secret or generates one if needed.
     */
    private function getSessionEncryptionSecret(): string
    {
        /** @var mixed $key */
        $key = $_SESSION['encryption_key'] ?? null;
        if (is_string($key) && mb_strlen($key, '8bit') === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }

        $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $_SESSION['encryption_key'] = $key;

        return $key;
    }

    public function cookieEncrypt(string $data, string $secret): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $ciphertext = sodium_crypto_secretbox($data, $nonce, $secret);
        } catch (Throwable) {
            return '';
        }

        return base64_encode($nonce . $ciphertext);
    }

    public function cookieDecrypt(string $encryptedData, string $secret): string|null
    {
        $encrypted = base64_decode($encryptedData);
        $nonce = mb_substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        try {
            $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $secret);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($decrypted)) {
            return null;
        }

        return $decrypted;
    }

    /**
     * Callback when user changes password.
     *
     * @param string $password New password to set
     */
    public function handlePasswordChange(string $password): void
    {
        $this->storePasswordCookie($password);
    }

    /**
     * Perform logout
     */
    public function logOut(): Response
    {
        $config = Config::getInstance();
        // -> delete password cookie(s)
        if ($config->config->LoginCookieDeleteAll) {
            foreach (array_keys($config->settings['Servers']) as $key) {
                $config->removeCookie('pmaAuth-' . $key);
                if (! $config->issetCookie('pmaAuth-' . $key)) {
                    continue;
                }

                $config->removeCookie('pmaAuth-' . $key);
            }
        } else {
            $cookieName = 'pmaAuth-' . Current::$server;
            $config->removeCookie($cookieName);
            if ($config->issetCookie($cookieName)) {
                $config->removeCookie($cookieName);
            }
        }

        return parent::logOut();
    }
}
