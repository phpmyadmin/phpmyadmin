<?php
/**
 * Cookie Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Session;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;
use phpseclib\Crypt;
use phpseclib\Crypt\Random;
use ReCaptcha;
use function base64_decode;
use function base64_encode;
use function class_exists;
use function count;
use function defined;
use function explode;
use function function_exists;
use function hash_equals;
use function hash_hmac;
use function in_array;
use function ini_get;
use function intval;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function openssl_error_string;
use function openssl_random_pseudo_bytes;
use function preg_match;
use function session_id;
use function strlen;
use function substr;
use function time;

/**
 * Handles the cookie authentication method
 */
class AuthenticationCookie extends AuthenticationPlugin
{
    /**
     * IV for encryption
     *
     * @var string|null
     */
    private $cookieIv = null;

    /**
     * Whether to use OpenSSL directly
     *
     * @var bool
     */
    private $useOpenSsl;

    public function __construct()
    {
        parent::__construct();
        $this->useOpenSsl = ! class_exists(Random::class);
    }

    /**
     * Forces (not)using of openSSL
     *
     * @param bool $use The flag
     *
     * @return void
     */
    public function setUseOpenSSL($use)
    {
        $this->useOpenSsl = $use;
    }

    /**
     * Displays authentication form
     *
     * this function MUST exit/quit the application
     *
     * @return bool|void
     *
     * @global string $conn_error the last connection error
     */
    public function showLoginForm()
    {
        global $conn_error, $route;

        $response = Response::getInstance();

        /**
         * When sending login modal after session has expired, send the
         * new token explicitly with the response to update the token
         * in all the forms having a hidden token.
         */
        $session_expired = isset($_REQUEST['check_timeout']) || isset($_REQUEST['session_timedout']);
        if (! $session_expired && $response->loginPage()) {
            if (defined('TESTSUITE')) {
                return true;
            }

            exit;
        }

        /**
         * When sending login modal after session has expired, send the
         * new token explicitly with the response to update the token
         * in all the forms having a hidden token.
         */
        if ($session_expired) {
            $response->setRequestStatus(false);
            $response->addJSON(
                'new_token',
                $_SESSION[' PMA_token ']
            );
        }

        /**
         * logged_in response parameter is used to check if the login,
         * using the modal was successful after session expiration.
         */
        if (isset($_REQUEST['session_timedout'])) {
            $response->addJSON(
                'logged_in',
                0
            );
        }

        // No recall if blowfish secret is not configured as it would produce
        // garbage
        if ($GLOBALS['cfg']['LoginCookieRecall']
            && ! empty($GLOBALS['cfg']['blowfish_secret'])
        ) {
            $default_user   = $this->user;
            $default_server = $GLOBALS['pma_auth_server'];
            $hasAutocomplete = true;
        } else {
            $default_user   = '';
            $default_server = '';
            $hasAutocomplete = false;
        }

        // wrap the login form in a div which overlays the whole page.
        if ($session_expired) {
            $loginHeader = $this->template->render('login/header', [
                'theme' => $GLOBALS['PMA_Theme'],
                'add_class' => ' modal_form',
                'session_expired' => 1,
            ]);
        } else {
            $loginHeader = $this->template->render('login/header', [
                'theme' => $GLOBALS['PMA_Theme'],
                'add_class' => '',
                'session_expired' => 0,
            ]);
        }

        $errorMessages = '';
        // Show error message
        if (! empty($conn_error)) {
            $errorMessages = Message::rawError((string) $conn_error)->getDisplay();
        } elseif (isset($_GET['session_expired'])
            && intval($_GET['session_expired']) == 1
        ) {
            $errorMessages = Message::rawError(
                __('Your session has expired. Please log in again.')
            )->getDisplay();
        }

        $language_manager = LanguageManager::getInstance();
        $languageSelector = '';
        $hasLanguages = empty($GLOBALS['cfg']['Lang']) && $language_manager->hasChoice();
        if ($hasLanguages) {
            $languageSelector = $language_manager->getSelectorDisplay(new Template(), true, false);
        }

        $serversOptions = '';
        $hasServers = count($GLOBALS['cfg']['Servers']) > 1;
        if ($hasServers) {
            $serversOptions = Select::render(false, false);
        }

        $_form_params = [];
        if (isset($route)) {
            $_form_params['route'] = $route;
        }
        if (strlen($GLOBALS['db'])) {
            $_form_params['db'] = $GLOBALS['db'];
        }
        if (strlen($GLOBALS['table'])) {
            $_form_params['table'] = $GLOBALS['table'];
        }

        $errors = '';
        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            $errors = $GLOBALS['error_handler']->getDispErrors();
        }

        // close the wrapping div tag, if the request is after session timeout
        if ($session_expired) {
            $loginFooter = $this->template->render('login/footer', ['session_expired' => 1]);
        } else {
            $loginFooter = $this->template->render('login/footer', ['session_expired' => 0]);
        }

        $configFooter = Config::renderFooter();

        echo $this->template->render('login/form', [
            'login_header' => $loginHeader,
            'is_demo' => $GLOBALS['cfg']['DBG']['demo'],
            'error_messages' => $errorMessages,
            'has_languages' => $hasLanguages,
            'language_selector' => $languageSelector,
            'is_session_expired' => $session_expired,
            'has_autocomplete' => $hasAutocomplete,
            'session_id' => session_id(),
            'is_arbitrary_server_allowed' => $GLOBALS['cfg']['AllowArbitraryServer'],
            'default_server' => $default_server,
            'default_user' => $default_user,
            'has_servers' => $hasServers,
            'server_options' => $serversOptions,
            'server' => $GLOBALS['server'],
            'lang' => $GLOBALS['lang'],
            'has_captcha' => ! empty($GLOBALS['cfg']['CaptchaApi'])
                && ! empty($GLOBALS['cfg']['CaptchaRequestParam'])
                && ! empty($GLOBALS['cfg']['CaptchaResponseParam'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPublicKey']),
            'use_captcha_checkbox' => ($GLOBALS['cfg']['CaptchaMethod'] ?? '') === 'checkbox',
            'captcha_api' => $GLOBALS['cfg']['CaptchaApi'],
            'captcha_req' => $GLOBALS['cfg']['CaptchaRequestParam'],
            'captcha_resp' => $GLOBALS['cfg']['CaptchaResponseParam'],
            'captcha_key' => $GLOBALS['cfg']['CaptchaLoginPublicKey'],
            'form_params' => $_form_params,
            'errors' => $errors,
            'login_footer' => $loginFooter,
            'config_footer' => $configFooter,
        ]);

        if (! defined('TESTSUITE')) {
            exit;
        }

        return true;
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
     * @return bool whether we get authentication settings or not
     */
    public function readCredentials()
    {
        global $conn_error;

        // Initialization
        /**
         * @global $GLOBALS['pma_auth_server'] the user provided server to
         * connect to
         */
        $GLOBALS['pma_auth_server'] = '';

        $this->user = $this->password = '';
        $GLOBALS['from_cookie'] = false;

        if (isset($_POST['pma_username']) && strlen($_POST['pma_username']) > 0) {
            // Verify Captcha if it is required.
            if (! empty($GLOBALS['cfg']['CaptchaApi'])
                && ! empty($GLOBALS['cfg']['CaptchaRequestParam'])
                && ! empty($GLOBALS['cfg']['CaptchaResponseParam'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
            ) {
                if (empty($_POST[$GLOBALS['cfg']['CaptchaResponseParam']])) {
                    $conn_error = __('Missing reCAPTCHA verification, maybe it has been blocked by adblock?');

                    return false;
                }

                $captchaSiteVerifyURL = $GLOBALS['cfg']['CaptchaSiteVerifyURL'] ?? '';
                $captchaSiteVerifyURL = empty($captchaSiteVerifyURL) ? null : $captchaSiteVerifyURL;
                if (function_exists('curl_init')) {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                        new ReCaptcha\RequestMethod\CurlPost(null, $captchaSiteVerifyURL)
                    );
                } elseif (ini_get('allow_url_fopen')) {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                        new ReCaptcha\RequestMethod\Post($captchaSiteVerifyURL)
                    );
                } else {
                    $reCaptcha = new ReCaptcha\ReCaptcha(
                        $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                        new ReCaptcha\RequestMethod\SocketPost(null, $captchaSiteVerifyURL)
                    );
                }

                // verify captcha status.
                $resp = $reCaptcha->verify(
                    $_POST[$GLOBALS['cfg']['CaptchaResponseParam']],
                    Core::getIp()
                );

                // Check if the captcha entered is valid, if not stop the login.
                if ($resp == null || ! $resp->isSuccess()) {
                    $codes = $resp->getErrorCodes();

                    if (in_array('invalid-json', $codes)) {
                        $conn_error = __('Failed to connect to the reCAPTCHA service!');
                    } else {
                        $conn_error = __('Entered captcha is wrong, try again!');
                    }

                    return false;
                }
            }

            // The user just logged in
            $this->user = Core::sanitizeMySQLUser($_POST['pma_username']);

            $password = $_POST['pma_password'] ?? '';
            if (strlen($password) >= 1000) {
                $conn_error = __('Your password is too long. To prevent denial-of-service attacks, ' .
                    'phpMyAdmin restricts passwords to less than 1000 characters.');

                return false;
            }
            $this->password = $password;

            if ($GLOBALS['cfg']['AllowArbitraryServer']
                && isset($_REQUEST['pma_servername'])
            ) {
                if ($GLOBALS['cfg']['ArbitraryServerRegexp']) {
                    $parts = explode(' ', $_REQUEST['pma_servername']);
                    if (count($parts) === 2) {
                        $tmp_host = $parts[0];
                    } else {
                        $tmp_host = $_REQUEST['pma_servername'];
                    }

                    $match = preg_match(
                        $GLOBALS['cfg']['ArbitraryServerRegexp'],
                        $tmp_host
                    );
                    if (! $match) {
                        $conn_error = __(
                            'You are not allowed to log in to this MySQL server!'
                        );

                        return false;
                    }
                }
                $GLOBALS['pma_auth_server'] = Core::sanitizeMySQLHost($_REQUEST['pma_servername']);
            }
            /* Secure current session on login to avoid session fixation */
            Session::secure();

            return true;
        }

        // At the end, try to set the $this->user
        // and $this->password variables from cookies

        // check cookies
        $serverCookie = $GLOBALS['PMA_Config']->getCookie('pmaUser-' . $GLOBALS['server']);
        if (empty($serverCookie)) {
            return false;
        }

        $value = $this->cookieDecrypt(
            $serverCookie,
            $this->getEncryptionSecret()
        );

        if ($value === false) {
            return false;
        }

        $this->user = $value;
        // user was never logged in since session start
        if (empty($_SESSION['browser_access_time'])) {
            return false;
        }

        // User inactive too long
        $last_access_time = time() - $GLOBALS['cfg']['LoginCookieValidity'];
        foreach ($_SESSION['browser_access_time'] as $key => $value) {
            if ($value >= $last_access_time) {
                continue;
            }

            unset($_SESSION['browser_access_time'][$key]);
        }
        // All sessions expired
        if (empty($_SESSION['browser_access_time'])) {
            SessionCache::remove('is_create_db_priv');
            SessionCache::remove('is_reload_priv');
            SessionCache::remove('db_to_create');
            SessionCache::remove('dbs_where_create_table_allowed');
            SessionCache::remove('dbs_to_test');
            SessionCache::remove('db_priv');
            SessionCache::remove('col_priv');
            SessionCache::remove('table_priv');
            SessionCache::remove('proc_priv');

            $this->showFailure('no-activity');
            if (! defined('TESTSUITE')) {
                exit;
            }

            return false;
        }

        // check password cookie
        $serverCookie = $GLOBALS['PMA_Config']->getCookie('pmaAuth-' . $GLOBALS['server']);

        if (empty($serverCookie)) {
            return false;
        }
        $value = $this->cookieDecrypt(
            $serverCookie,
            $this->getSessionEncryptionSecret()
        );
        if ($value === false) {
            return false;
        }

        $auth_data = json_decode($value, true);

        if (! is_array($auth_data) || ! isset($auth_data['password'])) {
            return false;
        }
        $this->password = $auth_data['password'];
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($auth_data['server'])) {
            $GLOBALS['pma_auth_server'] = $auth_data['server'];
        }

        $GLOBALS['from_cookie'] = true;

        return true;
    }

    /**
     * Set the user and password after last checkings if required
     *
     * @return bool always true
     */
    public function storeCredentials()
    {
        global $cfg;

        if ($GLOBALS['cfg']['AllowArbitraryServer']
            && ! empty($GLOBALS['pma_auth_server'])
        ) {
            /* Allow to specify 'host port' */
            $parts = explode(' ', $GLOBALS['pma_auth_server']);
            if (count($parts) === 2) {
                $tmp_host = $parts[0];
                $tmp_port = $parts[1];
            } else {
                $tmp_host = $GLOBALS['pma_auth_server'];
                $tmp_port = '';
            }
            if ($cfg['Server']['host'] != $GLOBALS['pma_auth_server']) {
                $cfg['Server']['host'] = $tmp_host;
                if (! empty($tmp_port)) {
                    $cfg['Server']['port'] = $tmp_port;
                }
            }
            unset($tmp_host, $tmp_port, $parts);
        }

        return parent::storeCredentials();
    }

    /**
     * Stores user credentials after successful login.
     *
     * @return void|bool
     */
    public function rememberCredentials()
    {
        global $route;

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
        $url_params = [];
        if (isset($route)) {
            $url_params['route'] = $route;
        }
        if (strlen($GLOBALS['db']) > 0) {
            $url_params['db'] = $GLOBALS['db'];
        }
        if (strlen($GLOBALS['table']) > 0) {
            $url_params['table'] = $GLOBALS['table'];
        }

        // user logged in successfully after session expiration
        if (isset($_REQUEST['session_timedout'])) {
            $response = Response::getInstance();
            $response->addJSON(
                'logged_in',
                1
            );
            $response->addJSON(
                'success',
                1
            );
            $response->addJSON(
                'new_token',
                $_SESSION[' PMA_token ']
            );

            if (! defined('TESTSUITE')) {
                exit;
            }

            return false;
        }
        // Set server cookies if required (once per session) and, in this case,
        // force reload to ensure the client accepts cookies
        if (! $GLOBALS['from_cookie']) {

            /**
             * Clear user cache.
             */
            Util::clearUserCache();

            Response::getInstance()
                ->disable();

            Core::sendHeaderLocation(
                './index.php?route=/' . Url::getCommonRaw($url_params, '&'),
                true
            );
            if (! defined('TESTSUITE')) {
                exit;
            }

            return false;
        }

        return true;
    }

    /**
     * Stores username in a cookie.
     *
     * @param string $username User name
     *
     * @return void
     */
    public function storeUsernameCookie($username)
    {
        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        $GLOBALS['PMA_Config']->setCookie(
            'pmaUser-' . $GLOBALS['server'],
            $this->cookieEncrypt(
                $username,
                $this->getEncryptionSecret()
            )
        );
    }

    /**
     * Stores password in a cookie.
     *
     * @param string $password Password
     *
     * @return void
     */
    public function storePasswordCookie($password)
    {
        $payload = ['password' => $password];
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($GLOBALS['pma_auth_server'])) {
            $payload['server'] = $GLOBALS['pma_auth_server'];
        }
        // Duration = as configured
        $GLOBALS['PMA_Config']->setCookie(
            'pmaAuth-' . $GLOBALS['server'],
            $this->cookieEncrypt(
                json_encode($payload),
                $this->getSessionEncryptionSecret()
            ),
            null,
            (int) $GLOBALS['cfg']['LoginCookieStore']
        );
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * prepares error message and switches to showLoginForm() which display the error
     * and the login form
     *
     * this function MUST exit/quit the application,
     * currently done by call to showLoginForm()
     *
     * @param string $failure String describing why authentication has failed
     *
     * @return void
     */
    public function showFailure($failure)
    {
        global $conn_error;

        parent::showFailure($failure);

        // Deletes password cookie and displays the login form
        $GLOBALS['PMA_Config']->removeCookie('pmaAuth-' . $GLOBALS['server']);

        $conn_error = $this->getErrorMessage($failure);

        $response = Response::getInstance();

        // needed for PHP-CGI (not need for FastCGI or mod-php)
        $response->header('Cache-Control: no-store, no-cache, must-revalidate');
        $response->header('Pragma: no-cache');

        $this->showLoginForm();
    }

    /**
     * Returns blowfish secret or generates one if needed.
     *
     * @return string
     */
    private function getEncryptionSecret()
    {
        if (empty($GLOBALS['cfg']['blowfish_secret'])) {
            return $this->getSessionEncryptionSecret();
        }

        return $GLOBALS['cfg']['blowfish_secret'];
    }

    /**
     * Returns blowfish secret or generates one if needed.
     *
     * @return string
     */
    private function getSessionEncryptionSecret()
    {
        if (empty($_SESSION['encryption_key'])) {
            if ($this->useOpenSsl) {
                $_SESSION['encryption_key'] = openssl_random_pseudo_bytes(32);
            } else {
                $_SESSION['encryption_key'] = Crypt\Random::string(32);
            }
        }

        return $_SESSION['encryption_key'];
    }

    /**
     * Concatenates secret in order to make it 16 bytes log
     *
     * This doesn't add any security, just ensures the secret
     * is long enough by copying it.
     *
     * @param string $secret Original secret
     *
     * @return string
     */
    public function enlargeSecret($secret)
    {
        while (strlen($secret) < 16) {
            $secret .= $secret;
        }

        return substr($secret, 0, 16);
    }

    /**
     * Derives MAC secret from encryption secret.
     *
     * @param string $secret the secret
     *
     * @return string the MAC secret
     */
    public function getMACSecret($secret)
    {
        // Grab first part, up to 16 chars
        // The MAC and AES secrets can overlap if original secret is short
        $length = strlen($secret);
        if ($length > 16) {
            return substr($secret, 0, 16);
        }

        return $this->enlargeSecret(
            $length == 1 ? $secret : substr($secret, 0, -1)
        );
    }

    /**
     * Derives AES secret from encryption secret.
     *
     * @param string $secret the secret
     *
     * @return string the AES secret
     */
    public function getAESSecret($secret)
    {
        // Grab second part, up to 16 chars
        // The MAC and AES secrets can overlap if original secret is short
        $length = strlen($secret);
        if ($length > 16) {
            return substr($secret, -16);
        }

        return $this->enlargeSecret(
            $length == 1 ? $secret : substr($secret, 1)
        );
    }

    /**
     * Cleans any SSL errors
     *
     * This can happen from corrupted cookies, by invalid encryption
     * parameters used in older phpMyAdmin versions or by wrong openSSL
     * configuration.
     *
     * In neither case the error is useful to user, but we need to clear
     * the error buffer as otherwise the errors would pop up later, for
     * example during MySQL SSL setup.
     *
     * @return void
     */
    public function cleanSSLErrors()
    {
        if (! function_exists('openssl_error_string')) {
            return;
        }

        do {
            $hasSslErrors = openssl_error_string();
        } while ($hasSslErrors !== false);
    }

    /**
     * Encryption using openssl's AES or phpseclib's AES
     * (phpseclib uses another extension when it is available)
     *
     * @param string $data   original data
     * @param string $secret the secret
     *
     * @return string the encrypted result
     */
    public function cookieEncrypt($data, $secret)
    {
        $mac_secret = $this->getMACSecret($secret);
        $aes_secret = $this->getAESSecret($secret);
        $iv = $this->createIV();
        if ($this->useOpenSsl) {
            $result = openssl_encrypt(
                $data,
                'AES-128-CBC',
                $aes_secret,
                0,
                $iv
            );
        } else {
            $cipher = new Crypt\AES(Crypt\Base::MODE_CBC);
            $cipher->setIV($iv);
            $cipher->setKey($aes_secret);
            $result = base64_encode($cipher->encrypt($data));
        }
        $this->cleanSSLErrors();
        $iv = base64_encode($iv);

        return json_encode(
            [
                'iv' => $iv,
                'mac' => hash_hmac('sha1', $iv . $result, $mac_secret),
                'payload' => $result,
            ]
        );
    }

    /**
     * Decryption using openssl's AES or phpseclib's AES
     * (phpseclib uses another extension when it is available)
     *
     * @param string $encdata encrypted data
     * @param string $secret  the secret
     *
     * @return string|false original data, false on error
     */
    public function cookieDecrypt($encdata, $secret)
    {
        $data = json_decode($encdata, true);

        if (! isset($data['mac'], $data['iv'], $data['payload'])
            || ! is_array($data)
            || ! is_string($data['mac'])
            || ! is_string($data['iv'])
            || ! is_string($data['payload'])
        ) {
            return false;
        }

        $mac_secret = $this->getMACSecret($secret);
        $aes_secret = $this->getAESSecret($secret);
        $newmac = hash_hmac('sha1', $data['iv'] . $data['payload'], $mac_secret);

        if (! hash_equals($data['mac'], $newmac)) {
            return false;
        }

        if ($this->useOpenSsl) {
            $result = openssl_decrypt(
                $data['payload'],
                'AES-128-CBC',
                $aes_secret,
                0,
                base64_decode($data['iv'])
            );
        } else {
            $cipher = new Crypt\AES(Crypt\Base::MODE_CBC);
            $cipher->setIV(base64_decode($data['iv']));
            $cipher->setKey($aes_secret);
            $result = $cipher->decrypt(base64_decode($data['payload']));
        }
        $this->cleanSSLErrors();

        return $result;
    }

    /**
     * Returns size of IV for encryption.
     *
     * @return int
     */
    public function getIVSize()
    {
        if ($this->useOpenSsl) {
            return openssl_cipher_iv_length('AES-128-CBC');
        }

        return (new Crypt\AES(Crypt\Base::MODE_CBC))->block_size;
    }

    /**
     * Initialization
     * Store the initialization vector because it will be needed for
     * further decryption. I don't think necessary to have one iv
     * per server so I don't put the server number in the cookie name.
     *
     * @return string
     */
    public function createIV()
    {
        /* Testsuite shortcut only to allow predictable IV */
        if ($this->cookieIv !== null) {
            return $this->cookieIv;
        }
        if ($this->useOpenSsl) {
            return openssl_random_pseudo_bytes(
                $this->getIVSize()
            );
        }

        return Crypt\Random::string(
            $this->getIVSize()
        );
    }

    /**
     * Sets encryption IV to use
     *
     * This is for testing only!
     *
     * @param string $vector The IV
     *
     * @return void
     */
    public function setIV($vector)
    {
        $this->cookieIv = $vector;
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
        $this->storePasswordCookie($password);
    }

    /**
     * Perform logout
     *
     * @return void
     */
    public function logOut()
    {
        global $PMA_Config;

        // -> delete password cookie(s)
        if ($GLOBALS['cfg']['LoginCookieDeleteAll']) {
            foreach ($GLOBALS['cfg']['Servers'] as $key => $val) {
                $PMA_Config->removeCookie('pmaAuth-' . $key);
                if (! $PMA_Config->issetCookie('pmaAuth-' . $key)) {
                    continue;
                }

                $PMA_Config->removeCookie('pmaAuth-' . $key);
            }
        } else {
            $cookieName = 'pmaAuth-' . $GLOBALS['server'];
            $PMA_Config->removeCookie($cookieName);
            if ($PMA_Config->issetCookie($cookieName)) {
                $PMA_Config->removeCookie($cookieName);
            }
        }
        parent::logOut();
    }
}
