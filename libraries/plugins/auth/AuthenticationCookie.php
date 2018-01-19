<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Cookie Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage Cookie
 */
namespace PMA\libraries\plugins\auth;

use phpseclib\Crypt;
use PMA\libraries\LanguageManager;
use PMA\libraries\Message;
use PMA\libraries\plugins\AuthenticationPlugin;
use PMA\libraries\Response;
use PMA\libraries\Util;
use PMA\libraries\Config;
use ReCaptcha;
use PMA\libraries\URL;

require_once './libraries/session.lib.php';

/**
 * Remember where to redirect the user
 * in case of an expired session.
 */
if (! empty($_REQUEST['target'])) {
    $GLOBALS['target'] = $_REQUEST['target'];
} else if (PMA_getenv('SCRIPT_NAME')) {
    $GLOBALS['target'] = basename(PMA_getenv('SCRIPT_NAME'));
}

/**
 * Handles the cookie authentication method
 *
 * @package PhpMyAdmin-Authentication
 */
class AuthenticationCookie extends AuthenticationPlugin
{
    /**
     * IV for encryption
     */
    private $_cookie_iv = null;

    /**
     * Whether to use OpenSSL directly
     */
    private $_use_openssl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_use_openssl = ! class_exists('phpseclib\Crypt\Random');
    }

    /**
     * Forces (not)using of openSSL
     *
     * @param boolean $use The flag
     *
     * @return void
     */
    public function setUseOpenSSL($use)
    {
        $this->_use_openssl = $use;
    }

    /**
     * Displays authentication form
     *
     * this function MUST exit/quit the application
     *
     * @global string $conn_error the last connection error
     *
     * @return boolean|void
     */
    public function auth()
    {
        global $conn_error;

        $response = Response::getInstance();
        if ($response->isAjax()) {
            $response->setRequestStatus(false);
            // redirect_flag redirects to the login page
            $response->addJSON('redirect_flag', '1');
            if (defined('TESTSUITE')) {
                return true;
            } else {
                exit;
            }
        }

        // No recall if blowfish secret is not configured as it would produce
        // garbage
        if ($GLOBALS['cfg']['LoginCookieRecall']
            && ! empty($GLOBALS['cfg']['blowfish_secret'])
        ) {
            $default_user   = $GLOBALS['PHP_AUTH_USER'];
            $default_server = $GLOBALS['pma_auth_server'];
            $autocomplete   = '';
        } else {
            $default_user   = '';
            $default_server = '';
            // skip the IE autocomplete feature.
            $autocomplete   = ' autocomplete="off"';
        }

        $response->getFooter()->setMinimal();
        $header = $response->getHeader();
        $header->setBodyId('loginform');
        $header->setTitle('phpMyAdmin');
        $header->disableMenuAndConsole();
        $header->disableWarnings();

        echo '
    <div class="container">
    <a href="';
        echo PMA_linkURL('https://www.phpmyadmin.net/');
        echo '" target="_blank" rel="noopener noreferrer" class="logo">';
        $logo_image = $GLOBALS['pmaThemeImage'] . 'logo_right.png';
        if (@file_exists($logo_image)) {
            echo '<img src="' , $logo_image
                , '" id="imLogo" name="imLogo" alt="phpMyAdmin" border="0" />';
        } else {
            echo '<img name="imLogo" id="imLogo" src="'
                , $GLOBALS['pmaThemeImage'] , 'pma_logo.png' , '" '
                , 'border="0" width="88" height="31" alt="phpMyAdmin" />';
        }
        echo '</a>
       <h1>';
        echo sprintf(
            __('Welcome to %s'),
            '<bdo dir="ltr" lang="en">phpMyAdmin</bdo>'
        );
        echo "</h1>";

        if ($GLOBALS['cfg']['DBG']['demo']) {
            echo '<fieldset>';
            echo '<legend>' , __('phpMyAdmin Demo Server') , '</legend>';
            printf(
                __(
                    'You are using the demo server. You can do anything here, but '
                    . 'please do not change root, debian-sys-maint and pma users. '
                    . 'More information is available at %s.'
                ),
                '<a href="url.php?url=https://demo.phpmyadmin.net/" target="_blank" rel="noopener noreferrer">demo.phpmyadmin.net</a>'
            );
            echo '</fieldset>';
        }

        // Show error message
        if (! empty($conn_error)) {
            Message::rawError($conn_error)->display();
        } elseif (isset($_GET['session_expired'])
            && intval($_GET['session_expired']) == 1
        ) {
            Message::rawError(
                __('Your session has expired. Please log in again.')
            )->display();
        }

        echo "<noscript>\n";
        Message::error(
            __("Javascript must be enabled past this point!")
        )->display();
        echo "</noscript>\n";

        // Displays the languages form
        $language_manager = LanguageManager::getInstance();
        if (empty($GLOBALS['cfg']['Lang']) && $language_manager->hasChoice()) {
            echo "<div class='hide js-show'>";
            // use fieldset, don't show doc link
            echo $language_manager->getSelectorDisplay(true, false);
            echo '</div>';
        }
        echo '
    <br />
    <!-- Login form -->
    <form method="post" action="index.php" name="login_form"' , $autocomplete ,
            ' class="disableAjax login hide js-show">
        <fieldset>
        <legend>';
        echo __('Log in');
        echo Util::showDocu('index');
        echo '</legend>';
        if ($GLOBALS['cfg']['AllowArbitraryServer']) {
            echo '
            <div class="item">
                <label for="input_servername" title="';
            echo __(
                'You can enter hostname/IP address and port separated by space.'
            );
            echo '">';
            echo __('Server:');
            echo '</label>
                <input type="text" name="pma_servername" id="input_servername"';
            echo ' value="';
            echo htmlspecialchars($default_server);
            echo '" size="24" class="textfield" title="';
            echo __(
                'You can enter hostname/IP address and port separated by space.'
            ); echo '" />
            </div>';
        }
            echo '<div class="item">
                <label for="input_username">' , __('Username:') , '</label>
                <input type="text" name="pma_username" id="input_username" '
                , 'value="' , htmlspecialchars($default_user) , '" size="24"'
                , ' class="textfield"/>
            </div>
            <div class="item">
                <label for="input_password">' , __('Password:') , '</label>
                <input type="password" name="pma_password" id="input_password"'
                , ' value="" size="24" class="textfield" />
            </div>';
        if (count($GLOBALS['cfg']['Servers']) > 1) {
            echo '<div class="item">
                <label for="select_server">' . __('Server Choice:') . '</label>
                <select name="server" id="select_server"';
            if ($GLOBALS['cfg']['AllowArbitraryServer']) {
                echo ' onchange="document.forms[\'login_form\'].'
                    , 'elements[\'pma_servername\'].value = \'\'" ';
            }
            echo '>';

            include_once './libraries/select_server.lib.php';
            echo PMA_selectServer(false, false);

            echo '</select></div>';
        } else {
            echo '    <input type="hidden" name="server" value="'
                , $GLOBALS['server'] , '" />';
        } // end if (server choice)

        // Add captcha input field if reCaptcha is enabled
        if (!empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
            && !empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
        ) {
            // If enabled show captcha to the user on the login screen.
            echo '<script src="https://www.google.com/recaptcha/api.js?hl='
                , $GLOBALS['lang'] , '" async defer></script>';
            echo '<div class="g-recaptcha" data-sitekey="'
                , htmlspecialchars($GLOBALS['cfg']['CaptchaLoginPublicKey']) , '"></div>';
        }

        echo '</fieldset>
        <fieldset class="tblFooters">
            <input value="' , __('Go') , '" type="submit" id="input_go" />';
        $_form_params = array();
        if (! empty($GLOBALS['target'])) {
            $_form_params['target'] = $GLOBALS['target'];
        }
        if (! empty($GLOBALS['db'])) {
            $_form_params['db'] = $GLOBALS['db'];
        }
        if (! empty($GLOBALS['table'])) {
            $_form_params['table'] = $GLOBALS['table'];
        }
        // do not generate a "server" hidden field as we want the "server"
        // drop-down to have priority
        echo URL::getHiddenInputs($_form_params, '', 0, 'server');
        echo '</fieldset>
    </form>';

        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            echo '<div id="pma_errors">';
            $GLOBALS['error_handler']->dispErrors();
            echo '</div>';
        }
        echo '</div>';
        echo Config::renderFooter();
        if (! defined('TESTSUITE')) {
            exit;
        } else {
            return true;
        }
    }

    /**
     * Gets advanced authentication settings
     *
     * this function DOES NOT check authentication - it just checks/provides
     * authentication credentials required to connect to the MySQL server
     * usually with $GLOBALS['dbi']->connect()
     *
     * it returns false if something is missing - which usually leads to
     * auth() which displays login form
     *
     * it returns true if all seems ok which usually leads to auth_set_user()
     *
     * it directly switches to authFails() if user inactivity timeout is reached
     *
     * @return boolean   whether we get authentication settings or not
     */
    public function authCheck()
    {
        global $conn_error;

        // Initialization
        /**
         * @global $GLOBALS['pma_auth_server'] the user provided server to
         * connect to
         */
        $GLOBALS['pma_auth_server'] = '';

        $GLOBALS['PHP_AUTH_USER'] = $GLOBALS['PHP_AUTH_PW'] = '';
        $GLOBALS['from_cookie'] = false;

        if (isset($_REQUEST['pma_username']) && strlen($_REQUEST['pma_username']) > 0) {

            // Verify Captcha if it is required.
            if (! empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
                && ! empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
            ) {
                if (! empty($_POST["g-recaptcha-response"])) {
                    if (function_exists('curl_init')) {
                        $reCaptcha = new ReCaptcha\ReCaptcha(
                            $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                            new ReCaptcha\RequestMethod\CurlPost()
                        );
                    } else if (ini_get('allow_url_fopen')) {
                        $reCaptcha = new ReCaptcha\ReCaptcha(
                            $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                            new ReCaptcha\RequestMethod\Post()
                        );
                    } else {
                        $reCaptcha = new ReCaptcha\ReCaptcha(
                            $GLOBALS['cfg']['CaptchaLoginPrivateKey'],
                            new ReCaptcha\RequestMethod\SocketPost()
                        );
                    }

                    // verify captcha status.
                    $resp = $reCaptcha->verify(
                        $_POST["g-recaptcha-response"],
                        PMA_getIp()
                    );

                    // Check if the captcha entered is valid, if not stop the login.
                    if ($resp == null || ! $resp->isSuccess()) {
                        $conn_error = __('Entered captcha is wrong, try again!');
                        return false;
                    }
                } else {
                    $conn_error = __('Please enter correct captcha!');
                    return false;
                }
            }

            // The user just logged in
            $GLOBALS['PHP_AUTH_USER'] = PMA_sanitizeMySQLUser($_REQUEST['pma_username']);
            $GLOBALS['PHP_AUTH_PW'] = isset($_REQUEST['pma_password']) ? $_REQUEST['pma_password'] : '';
            if ($GLOBALS['cfg']['AllowArbitraryServer']
                && isset($_REQUEST['pma_servername'])
            ) {
                if ($GLOBALS['cfg']['ArbitraryServerRegexp']) {
                    $parts = explode(' ', $_REQUEST['pma_servername']);
                    if (count($parts) == 2) {
                        $tmp_host = $parts[0];
                    } else {
                        $tmp_host = $_REQUEST['pma_servername'];
                    }

                    $match = preg_match(
                        $GLOBALS['cfg']['ArbitraryServerRegexp'], $tmp_host
                    );
                    if (! $match) {
                        $conn_error = __(
                            'You are not allowed to log in to this MySQL server!'
                        );
                        return false;
                    }
                }
                $GLOBALS['pma_auth_server'] = PMA_sanitizeMySQLHost($_REQUEST['pma_servername']);
            }
            PMA_secureSession();
            return true;
        }

        // At the end, try to set the $GLOBALS['PHP_AUTH_USER']
        // and $GLOBALS['PHP_AUTH_PW'] variables from cookies

        // check cookies
        if (empty($_COOKIE['pmaUser-' . $GLOBALS['server']])) {
            return false;
        }

        $GLOBALS['PHP_AUTH_USER'] = $this->cookieDecrypt(
            $_COOKIE['pmaUser-' . $GLOBALS['server']],
            $this->_getEncryptionSecret()
        );

        // user was never logged in since session start
        if (empty($_SESSION['browser_access_time'])) {
            return false;
        }

        // User inactive too long
        $last_access_time = time() - $GLOBALS['cfg']['LoginCookieValidity'];
        foreach ($_SESSION['browser_access_time'] as $key => $value) {
            if ($value < $last_access_time) {
                unset($_SESSION['browser_access_time'][$key]);
            }
        }
        // All sessions expired
        if (empty($_SESSION['browser_access_time'])) {
            Util::cacheUnset('is_create_db_priv');
            Util::cacheUnset('is_reload_priv');
            Util::cacheUnset('db_to_create');
            Util::cacheUnset('dbs_where_create_table_allowed');
            Util::cacheUnset('dbs_to_test');
            Util::cacheUnset('db_priv');
            Util::cacheUnset('col_priv');
            Util::cacheUnset('table_priv');
            Util::cacheUnset('proc_priv');

            $GLOBALS['no_activity'] = true;
            $this->authFails();
            if (! defined('TESTSUITE')) {
                exit;
            } else {
                return false;
            }
        }

        // check password cookie
        if (empty($_COOKIE['pmaAuth-' . $GLOBALS['server']])) {
            return false;
        }

        $auth_data = json_decode(
            $this->cookieDecrypt(
                $_COOKIE['pmaAuth-' . $GLOBALS['server']],
                $this->_getSessionEncryptionSecret()
            ),
            true
        );

        if (! is_array($auth_data) || ! isset($auth_data['password'])) {
            return false;
        }
        $GLOBALS['PHP_AUTH_PW'] = $auth_data['password'];
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($auth_data['server'])) {
            $GLOBALS['pma_auth_server'] = $auth_data['server'];
        }

        $GLOBALS['from_cookie'] = true;

        return true;
    }

    /**
     * Set the user and password after last checkings if required
     *
     * @return boolean always true
     */
    public function authSetUser()
    {
        global $cfg;

        // Ensures valid authentication mode, 'only_db', bookmark database and
        // table names and relation table name are used
        if (! hash_equals($cfg['Server']['user'], $GLOBALS['PHP_AUTH_USER'])) {
            foreach ($cfg['Servers'] as $idx => $current) {
                if ($current['host'] == $cfg['Server']['host']
                    && $current['port'] == $cfg['Server']['port']
                    && $current['socket'] == $cfg['Server']['socket']
                    && $current['ssl'] == $cfg['Server']['ssl']
                    && hash_equals($current['user'], $GLOBALS['PHP_AUTH_USER'])
                ) {
                    $GLOBALS['server'] = $idx;
                    $cfg['Server']     = $current;
                    break;
                }
            } // end foreach
        } // end if

        if ($GLOBALS['cfg']['AllowArbitraryServer']
            && ! empty($GLOBALS['pma_auth_server'])
        ) {
            /* Allow to specify 'host port' */
            $parts = explode(' ', $GLOBALS['pma_auth_server']);
            if (count($parts) == 2) {
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
        $cfg['Server']['user']     = $GLOBALS['PHP_AUTH_USER'];
        $cfg['Server']['password'] = $GLOBALS['PHP_AUTH_PW'];

        // Avoid showing the password in phpinfo()'s output
        unset($GLOBALS['PHP_AUTH_PW']);
        unset($_SERVER['PHP_AUTH_PW']);
        $this->setSessionAccessTime();
    }

    /**
     * Stores user credentials after successful login.
     *
     * @return void|bool
     */
    public function storeUserCredentials()
    {
        global $cfg;

        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        $this->storeUsernameCookie($cfg['Server']['user']);

        // Duration = as configured
        // Do not store password cookie on password change as we will
        // set the cookie again after password has been changed
        if (! isset($_POST['change_pw'])) {
            $this->storePasswordCookie($cfg['Server']['password']);
        }

        // Set server cookies if required (once per session) and, in this case,
        // force reload to ensure the client accepts cookies
        if (! $GLOBALS['from_cookie']) {
            // URL where to go:
            $redirect_url = './index.php';

            // any parameters to pass?
            $url_params = array();
            if (strlen($GLOBALS['db']) > 0) {
                $url_params['db'] = $GLOBALS['db'];
            }
            if (strlen($GLOBALS['table']) > 0) {
                $url_params['table'] = $GLOBALS['table'];
            }
            // any target to pass?
            if (! empty($GLOBALS['target'])
                && $GLOBALS['target'] != 'index.php'
            ) {
                $url_params['target'] = $GLOBALS['target'];
            }

            /**
             * Clear user cache.
             */
            Util::clearUserCache();

            Response::getInstance()
                ->disable();

            PMA_sendHeaderLocation(
                $redirect_url . URL::getCommonRaw($url_params),
                true
            );
            if (! defined('TESTSUITE')) {
                exit;
            } else {
                return false;
            }
        } // end if

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
                $this->_getEncryptionSecret()
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
        $payload = array('password' => $password);
        if ($GLOBALS['cfg']['AllowArbitraryServer'] && ! empty($GLOBALS['pma_auth_server'])) {
            $payload['server'] = $GLOBALS['pma_auth_server'];
        }
        // Duration = as configured
        $GLOBALS['PMA_Config']->setCookie(
            'pmaAuth-' . $GLOBALS['server'],
            $this->cookieEncrypt(
                json_encode($payload),
                $this->_getSessionEncryptionSecret()
            ),
            null,
            $GLOBALS['cfg']['LoginCookieStore']
        );
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     *
     * prepares error message and switches to auth() which display the error
     * and the login form
     *
     * this function MUST exit/quit the application,
     * currently done by call to auth()
     *
     * @return void
     */
    public function authFails()
    {
        global $conn_error;

        // Deletes password cookie and displays the login form
        $GLOBALS['PMA_Config']->removeCookie('pmaAuth-' . $GLOBALS['server']);

        $conn_error = $this->getErrorMessage();

        $response = Response::getInstance();

        // needed for PHP-CGI (not need for FastCGI or mod-php)
        $response->header('Cache-Control: no-store, no-cache, must-revalidate');
        $response->header('Pragma: no-cache');

        $this->auth();
    }

    /**
     * Returns blowfish secret or generates one if needed.
     *
     * @return string
     */
    private function _getEncryptionSecret()
    {
        if (empty($GLOBALS['cfg']['blowfish_secret'])) {
            return $this->_getSessionEncryptionSecret();
        } else {
            return $GLOBALS['cfg']['blowfish_secret'];
        }
    }

    /**
     * Returns blowfish secret or generates one if needed.
     *
     * @return string
     */
    private function _getSessionEncryptionSecret()
    {
        if (empty($_SESSION['encryption_key'])) {
            if ($this->_use_openssl) {
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
        if (function_exists('openssl_error_string')) {
            while (($ssl_err = openssl_error_string()) !== false) {
            }
        }
    }

    /**
     * Encryption using openssl's AES or phpseclib's AES
     * (phpseclib uses mcrypt when it is available)
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
        if ($this->_use_openssl) {
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
            array(
                'iv' => $iv,
                'mac' => hash_hmac('sha1', $iv . $result, $mac_secret),
                'payload' => $result,
            )
        );
    }

    /**
     * Decryption using openssl's AES or phpseclib's AES
     * (phpseclib uses mcrypt when it is available)
     *
     * @param string $encdata encrypted data
     * @param string $secret  the secret
     *
     * @return string|bool original data, false on error
     */
    public function cookieDecrypt($encdata, $secret)
    {
        $data = json_decode($encdata, true);

        if (! is_array($data) || ! isset($data['mac']) || ! isset($data['iv']) || ! isset($data['payload'])
            || ! is_string($data['mac']) || ! is_string($data['iv']) || ! is_string($data['payload'])
            ) {
            return false;
        }

        $mac_secret = $this->getMACSecret($secret);
        $aes_secret = $this->getAESSecret($secret);
        $newmac = hash_hmac('sha1', $data['iv'] . $data['payload'], $mac_secret);

        if (! hash_equals($data['mac'], $newmac)) {
            return false;
        }

        if ($this->_use_openssl) {
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
        if ($this->_use_openssl) {
            return openssl_cipher_iv_length('AES-128-CBC');
        }
        $cipher = new Crypt\AES(Crypt\Base::MODE_CBC);
        return $cipher->block_size;
    }

    /**
     * Initialization
     * Store the initialization vector because it will be needed for
     * further decryption. I don't think necessary to have one iv
     * per server so I don't put the server number in the cookie name.
     *
     * @return void
     */
    public function createIV()
    {
        /* Testsuite shortcut only to allow predictable IV */
        if (! is_null($this->_cookie_iv)) {
            return $this->_cookie_iv;
        }
        if ($this->_use_openssl) {
            return openssl_random_pseudo_bytes(
                $this->getIVSize()
            );
        } else {
            return Crypt\Random::string(
                $this->getIVSize()
            );
        }
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
        $this->_cookie_iv = $vector;
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
        // -> delete password cookie(s)
        if ($GLOBALS['cfg']['LoginCookieDeleteAll']) {
            foreach ($GLOBALS['cfg']['Servers'] as $key => $val) {
                $GLOBALS['PMA_Config']->removeCookie('pmaAuth-' . $key);
                if (isset($_COOKIE['pmaAuth-' . $key])) {
                    unset($_COOKIE['pmaAuth-' . $key]);
                }
            }
        } else {
            $GLOBALS['PMA_Config']->removeCookie(
                'pmaAuth-' . $GLOBALS['server']
            );
            if (isset($_COOKIE['pmaAuth-' . $GLOBALS['server']])) {
                unset($_COOKIE['pmaAuth-' . $GLOBALS['server']]);
            }
        }
        parent::logOut();
    }
}
