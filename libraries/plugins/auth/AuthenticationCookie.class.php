<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Cookie Authentication plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Authentication
 * @subpackage Cookie
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the authentication interface */
require_once 'libraries/plugins/AuthenticationPlugin.class.php';

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
 * Swekey authentication functions.
 */
require './libraries/plugins/auth/swekey/swekey.auth.lib.php';

/**
 * phpseclib
 */
if (! function_exists('openssl_encrypt')
    || ! function_exists('openssl_decrypt')
    || ! function_exists('openssl_random_pseudo_bytes')
    || PHP_VERSION_ID < 50304
) {
    require PHPSECLIB_INC_DIR . '/Crypt/AES.php';
    require PHPSECLIB_INC_DIR . '/Crypt/Random.php';
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

        $response = PMA_Response::getInstance();
        if ($response->isAjax()) {
            $response->isSuccess(false);
            // redirect_flag redirects to the login page
            $response->addJSON('redirect_flag', '1');
            if (defined('TESTSUITE')) {
                return true;
            } else {
                exit;
            }
        }

        /* Perform logout to custom URL */
        if (! empty($_REQUEST['old_usr'])
            && ! empty($GLOBALS['cfg']['Server']['LogoutURL'])
        ) {
            PMA_sendHeaderLocation($GLOBALS['cfg']['Server']['LogoutURL']);
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

        if (file_exists(CUSTOM_HEADER_FILE)) {
            include CUSTOM_HEADER_FILE;
        }
        echo '
    <div class="container">
    <a href="';
        echo PMA_linkURL('http://www.phpmyadmin.net/');
        echo '" target="_blank" class="logo">';
        $logo_image = $GLOBALS['pmaThemeImage'] . 'logo_right.png';
        if (@file_exists($logo_image)) {
            echo '<img src="' . $logo_image
                . '" id="imLogo" name="imLogo" alt="phpMyAdmin" border="0" />';
        } else {
            echo '<img name="imLogo" id="imLogo" src="'
                . $GLOBALS['pmaThemeImage'] . 'pma_logo.png' . '" '
                . 'border="0" width="88" height="31" alt="phpMyAdmin" />';
        }
        echo '</a>
       <h1>';
        echo sprintf(
            __('Welcome to %s'),
            '<bdo dir="ltr" lang="en">phpMyAdmin</bdo>'
        );
        echo "</h1>";

        // Show error message
        if (! empty($conn_error)) {
            PMA_Message::rawError($conn_error)->display();
        } elseif (isset($_GET['session_expired'])
            && intval($_GET['session_expired']) == 1
        ) {
            PMA_Message::rawError(
                __('Your session has expired. Please log in again.')
            )->display();
        }

        echo "<noscript>\n";
        PMA_message::error(
            __("Javascript must be enabled past this point!")
        )->display();
        echo "</noscript>\n";

        echo "<div class='hide js-show'>";
        // Displays the languages form
        if (empty($GLOBALS['cfg']['Lang'])) {
            include_once './libraries/display_select_lang.lib.php';
            // use fieldset, don't show doc link
            echo PMA_getLanguageSelectorHtml(true, false);
        }
        echo '</div>
    <br />
    <!-- Login form -->
    <form method="post" action="index.php" name="login_form"' . $autocomplete .
            ' class="disableAjax login hide js-show">
        <fieldset>
        <legend>';
        echo __('Log in');
        echo PMA_Util::showDocu('index');
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
                <label for="input_username">' . __('Username:') . '</label>
                <input type="text" name="pma_username" id="input_username" '
                . 'value="' . htmlspecialchars($default_user) . '" size="24"'
                . ' class="textfield"/>
            </div>
            <div class="item">
                <label for="input_password">' . __('Password:') . '</label>
                <input type="password" name="pma_password" id="input_password"'
                . ' value="" size="24" class="textfield" />
            </div>';
        if (count($GLOBALS['cfg']['Servers']) > 1) {
            echo '<div class="item">
                <label for="select_server">' . __('Server Choice:') . '</label>
                <select name="server" id="select_server"';
            if ($GLOBALS['cfg']['AllowArbitraryServer']) {
                echo ' onchange="document.forms[\'login_form\'].'
                    . 'elements[\'pma_servername\'].value = \'\'" ';
            }
            echo '>';

            include_once './libraries/select_server.lib.php';
            echo PMA_selectServer(false, false);

            echo '</select></div>';
        } else {
            echo '    <input type="hidden" name="server" value="'
                . $GLOBALS['server'] . '" />';
        } // end if (server choice)

        // We already have one correct captcha.
        $skip = false;
        if (  isset($_SESSION['last_valid_captcha'])
            && $_SESSION['last_valid_captcha']
        ) {
            $skip = true;
        }

        // Add captcha input field if reCaptcha is enabled
        if (  !empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
            && !empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
            && !$skip
        ) {
            // If enabled show captcha to the user on the login screen.
            echo '<script src="https://www.google.com/recaptcha/api.js?hl='
                . $GLOBALS['lang'] . '" async defer></script>';
            echo '<div class="g-recaptcha" data-sitekey="'
                . $GLOBALS['cfg']['CaptchaLoginPublicKey'] . '"></div>';
        }

        echo '</fieldset>
        <fieldset class="tblFooters">
            <input value="' . __('Go') . '" type="submit" id="input_go" />';
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
        echo PMA_URL_getHiddenInputs($_form_params, '', 0, 'server');
        echo '</fieldset>
    </form>';

        // BEGIN Swekey Integration
        Swekey_login('input_username', 'input_go');
        // END Swekey Integration

        if ($GLOBALS['error_handler']->hasDisplayErrors()) {
            echo '<div id="pma_errors">';
            $GLOBALS['error_handler']->dispErrors();
            echo '</div>';
        }
        echo '</div>';
        if (file_exists(CUSTOM_FOOTER_FILE)) {
            include CUSTOM_FOOTER_FILE;
        }
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

        // BEGIN Swekey Integration
        if (! Swekey_Auth_check()) {
            return false;
        }
        // END Swekey Integration

        if (defined('PMA_CLEAR_COOKIES')) {
            foreach ($GLOBALS['cfg']['Servers'] as $key => $val) {
                $GLOBALS['PMA_Config']->removeCookie('pmaPass-' . $key);
                $GLOBALS['PMA_Config']->removeCookie('pmaServer-' . $key);
                $GLOBALS['PMA_Config']->removeCookie('pmaUser-' . $key);
            }
            return false;
        }

        // We already have one correct captcha.
        $skip = false;
        if (  isset($_SESSION['last_valid_captcha'])
            && $_SESSION['last_valid_captcha']
        ) {
            $skip = true;
        }

        // Verify Captcha if it is required.
        if (  !empty($GLOBALS['cfg']['CaptchaLoginPrivateKey'])
            && !empty($GLOBALS['cfg']['CaptchaLoginPublicKey'])
            && !$skip
        ) {
            if (! empty($_POST["g-recaptcha-response"])) {

                include_once 'libraries/plugins/auth/recaptcha/recaptchalib.php';
                $reCaptcha = new ReCaptcha(
                    $GLOBALS['cfg']['CaptchaLoginPrivateKey']
                );

                // verify captcha status.
                $resp = $reCaptcha->verifyResponse(
                    $_SERVER["REMOTE_ADDR"],
                    $_POST["g-recaptcha-response"]
                );

                // Check if the captcha entered is valid, if not stop the login.
                if ($resp == null || ! $resp->success) {
                    $conn_error = __('Entered captcha is wrong, try again!');
                    $_SESSION['last_valid_captcha'] = false;
                    return false;
                } else {
                    $_SESSION['last_valid_captcha'] = true;
                }
            } else {
                if (! isset($_SESSION['last_valid_captcha'])
                    || ! $_SESSION['last_valid_captcha']
                ) {
                    $conn_error = __('Please enter correct captcha!');
                    return false;
                }
            }
        }

        if (! empty($_REQUEST['old_usr'])) {
            // The user wants to be logged out
            // -> delete his choices that were stored in session

            // according to the PHP manual we should do this before the destroy:
            //$_SESSION = array();

            if (! defined('TESTSUITE')) {
                session_destroy();
                // $_SESSION array is not immediately emptied
                $_SESSION['last_valid_captcha'] = false;
            }
            // -> delete password cookie(s)
            if ($GLOBALS['cfg']['LoginCookieDeleteAll']) {
                foreach ($GLOBALS['cfg']['Servers'] as $key => $val) {
                    $GLOBALS['PMA_Config']->removeCookie('pmaPass-' . $key);
                    if (isset($_COOKIE['pmaPass-' . $key])) {
                        unset($_COOKIE['pmaPass-' . $key]);
                    }
                }
            } else {
                $GLOBALS['PMA_Config']->removeCookie(
                    'pmaPass-' . $GLOBALS['server']
                );
                if (isset($_COOKIE['pmaPass-' . $GLOBALS['server']])) {
                    unset($_COOKIE['pmaPass-' . $GLOBALS['server']]);
                }
            }
        }

        if (! empty($_REQUEST['pma_username'])) {
            // The user just logged in
            $GLOBALS['PHP_AUTH_USER'] = $_REQUEST['pma_username'];
            $GLOBALS['PHP_AUTH_PW']   = empty($_REQUEST['pma_password'])
                ? ''
                : $_REQUEST['pma_password'];
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
                $GLOBALS['pma_auth_server'] = $_REQUEST['pma_servername'];
            }
            return true;
        }

        // At the end, try to set the $GLOBALS['PHP_AUTH_USER']
        // and $GLOBALS['PHP_AUTH_PW'] variables from cookies

        // servername
        if ($GLOBALS['cfg']['AllowArbitraryServer']
            && ! empty($_COOKIE['pmaServer-' . $GLOBALS['server']])
        ) {
            $GLOBALS['pma_auth_server']
                = $_COOKIE['pmaServer-' . $GLOBALS['server']];
        }

        // check cookies
        if (empty($_COOKIE['pmaUser-' . $GLOBALS['server']])
            || empty($_COOKIE['pma_iv-' . $GLOBALS['server']])
        ) {
            return false;
        }

        $GLOBALS['PHP_AUTH_USER'] = $this->cookieDecrypt(
            $_COOKIE['pmaUser-' . $GLOBALS['server']],
            $this->_getEncryptionSecret()
        );

        // user was never logged in since session start
        if (empty($_SESSION['last_access_time'])) {
            return false;
        }

        // User inactive too long
        $last_access_time = time() - $GLOBALS['cfg']['LoginCookieValidity'];
        if ($_SESSION['last_access_time'] < $last_access_time
        ) {
            PMA_Util::cacheUnset('is_create_db_priv');
            PMA_Util::cacheUnset('is_process_priv');
            PMA_Util::cacheUnset('is_reload_priv');
            PMA_Util::cacheUnset('db_to_create');
            PMA_Util::cacheUnset('dbs_where_create_table_allowed');
            PMA_Util::cacheUnset('dbs_to_test');
            $GLOBALS['no_activity'] = true;
            $this->authFails();
            if (! defined('TESTSUITE')) {
                exit;
            } else {
                return false;
            }
        }

        // check password cookie
        if (empty($_COOKIE['pmaPass-' . $GLOBALS['server']])) {
            return false;
        }

        $GLOBALS['PHP_AUTH_PW'] = $this->cookieDecrypt(
            $_COOKIE['pmaPass-' . $GLOBALS['server']],
            $this->_getSessionEncryptionSecret()
        );

        if ($GLOBALS['PHP_AUTH_PW'] == "\xff(blank)") {
            $GLOBALS['PHP_AUTH_PW'] = '';
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
        if ($cfg['Server']['user'] != $GLOBALS['PHP_AUTH_USER']) {
            foreach ($cfg['Servers'] as $idx => $current) {
                if ($current['host'] == $cfg['Server']['host']
                    && $current['port'] == $cfg['Server']['port']
                    && $current['socket'] == $cfg['Server']['socket']
                    && $current['ssl'] == $cfg['Server']['ssl']
                    && $current['connect_type'] == $cfg['Server']['connect_type']
                    && $current['user'] == $GLOBALS['PHP_AUTH_USER']
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
        if (isset($_REQUEST['access_time'])) {
            $_SESSION['last_access_time'] = time() - $_REQUEST['access_time'];
        } else {
            $_SESSION['last_access_time'] = time();
        }
    }

    /**
     * Stores user credentials after successful login.
     *
     * @return void|bool
     */
    public function storeUserCredentials()
    {
        global $cfg;

        $this->createIV();

        // Name and password cookies need to be refreshed each time
        // Duration = one month for username
        $this->storeUsernameCookie($cfg['Server']['user']);

        // Duration = as configured
        $this->storePasswordCookie($cfg['Server']['password']);

        // Set server cookies if required (once per session) and, in this case,
        // force reload to ensure the client accepts cookies
        if (! $GLOBALS['from_cookie']) {
            if ($GLOBALS['cfg']['AllowArbitraryServer']) {
                if (! empty($GLOBALS['pma_auth_server'])) {
                    // Duration = one month for servername
                    $GLOBALS['PMA_Config']->setCookie(
                        'pmaServer-' . $GLOBALS['server'],
                        $cfg['Server']['host']
                    );
                } else {
                    // Delete servername cookie
                    $GLOBALS['PMA_Config']->removeCookie(
                        'pmaServer-' . $GLOBALS['server']
                    );
                }
            }

            // URL where to go:
            $redirect_url = $cfg['PmaAbsoluteUri'] . 'index.php';

            // any parameters to pass?
            $url_params = array();
            if (/*overload*/mb_strlen($GLOBALS['db'])) {
                $url_params['db'] = $GLOBALS['db'];
            }
            if (/*overload*/mb_strlen($GLOBALS['table'])) {
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
            PMA_Util::clearUserCache();

            PMA_Response::getInstance()->disable();

            PMA_sendHeaderLocation(
                $redirect_url . PMA_URL_getCommon($url_params, 'text'),
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
        // Duration = as configured
        $GLOBALS['PMA_Config']->setCookie(
            'pmaPass-' . $GLOBALS['server'],
            $this->cookieEncrypt(
                ! empty($password) ? $password : "\xff(blank)",
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
        $GLOBALS['PMA_Config']->removeCookie('pmaPass-' . $GLOBALS['server']);

        $conn_error = $this->getErrorMessage();

        // needed for PHP-CGI (not need for FastCGI or mod-php)
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

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
            // apply md5() to work around too long secrets (returns 32 characters)
            return md5($GLOBALS['cfg']['blowfish_secret']);
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
            if ($this->_useOpenSSL()) {
                $_SESSION['encryption_key'] = openssl_random_pseudo_bytes(256);
            } else {
                $_SESSION['encryption_key'] = crypt_random_string(256);
            }
        }
        return $_SESSION['encryption_key'];
    }

    /**
     * Checks whether we should use openssl for encryption.
     *
     * @return boolean
     */
    private function _useOpenSSL()
    {
        return (
            function_exists('openssl_encrypt')
            && function_exists('openssl_decrypt')
            && function_exists('openssl_random_pseudo_bytes')
            && PHP_VERSION_ID >= 50304
        );
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
        if ($this->_useOpenSSL()) {
            return openssl_encrypt(
                $data,
                'AES-128-CBC',
                $secret,
                0,
                $this->_cookie_iv
            );
        } else {
            $cipher = new Crypt_AES(CRYPT_AES_MODE_CBC);
            $cipher->setIV($this->_cookie_iv);
            $cipher->setKey($secret);
            return base64_encode($cipher->encrypt($data));
        }
    }

    /**
     * Decryption using openssl's AES or phpseclib's AES
     * (phpseclib uses mcrypt when it is available)
     *
     * @param string $encdata encrypted data
     * @param string $secret  the secret
     *
     * @return string original data
     */
    public function cookieDecrypt($encdata, $secret)
    {
        if (is_null($this->_cookie_iv)) {
            $this->_cookie_iv = base64_decode($_COOKIE['pma_iv-' . $GLOBALS['server']], true);
        }
        if (strlen($this->_cookie_iv) < $this->getIVSize()) {
                $this->createIV();
        }

        if ($this->_useOpenSSL()) {
            return openssl_decrypt(
                $encdata,
                'AES-128-CBC',
                $secret,
                0,
                $this->_cookie_iv
            );
        } else {
            $cipher = new Crypt_AES(CRYPT_AES_MODE_CBC);
            $cipher->setIV($this->_cookie_iv);
            $cipher->setKey($secret);
            return $cipher->decrypt(base64_decode($encdata));
        }
    }

    /**
     * Returns size of IV for encryption.
     *
     * @return int
     */
    public function getIVSize()
    {
        if ($this->_useOpenSSL()) {
            return openssl_cipher_iv_length('AES-128-CBC');
        }
        $cipher = new Crypt_AES(CRYPT_AES_MODE_CBC);
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
        if ($this->_useOpenSSL()) {
            $this->_cookie_iv = openssl_random_pseudo_bytes(
                $this->getIVSize()
            );
        } else {
            $this->_cookie_iv = crypt_random_string(
                $this->getIVSize()
            );
        }
        $GLOBALS['PMA_Config']->setCookie(
            'pma_iv-' . $GLOBALS['server'],
            base64_encode($this->_cookie_iv)
        );
    }

    /**
     * Sets encryption IV to use
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
}
