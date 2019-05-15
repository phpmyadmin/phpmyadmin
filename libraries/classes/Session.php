<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Session handling
 *
 * @package PhpMyAdmin
 *
 * @see     https://secure.php.net/session
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Util;

/**
 * Session class
 *
 * @package PhpMyAdmin
 */
class Session
{
    /**
     * Generates PMA_token session variable.
     *
     * @return void
     */
    private static function generateToken()
    {
        $_SESSION[' PMA_token '] = Util::generateRandom(16, true);
        $_SESSION[' HMAC_secret '] = Util::generateRandom(16);

        /**
         * Check if token is properly generated (the generation can fail, for example
         * due to missing /dev/random for openssl).
         */
        if (empty($_SESSION[' PMA_token '])) {
            Core::fatalError(
                'Failed to generate random CSRF token!'
            );
        }
    }

    /**
     * tries to secure session from hijacking and fixation
     * should be called before login and after successful login
     * (only required if sensitive information stored in session)
     *
     * @return void
     */
    public static function secure()
    {
        // prevent session fixation and XSS
        if (session_status() === PHP_SESSION_ACTIVE && ! defined('TESTSUITE')) {
            session_regenerate_id(true);
        }
        // continue with empty session
        session_unset();
        self::generateToken();
    }

    /**
     * Session failed function
     *
     * @param array $errors PhpMyAdmin\ErrorHandler array
     *
     * @return void
     */
    private static function sessionFailed(array $errors)
    {
        $messages = array();
        foreach ($errors as $error) {
            /*
             * Remove path from open() in error message to avoid path disclossure
             *
             * This can happen with PHP 5 when nonexisting session ID is provided,
             * since PHP 7, session existence is checked first.
             *
             * This error can also happen in case of session backed error (eg.
             * read only filesystem) on any PHP version.
             *
             * The message string is currently hardcoded in PHP, so hopefully it
             * will not change in future.
             */
            $messages[] = preg_replace(
                '/open\(.*, O_RDWR\)/',
                'open(SESSION_FILE, O_RDWR)',
                htmlspecialchars($error->getMessage())
            );
        }

        /*
         * Session initialization is done before selecting language, so we
         * can not use translations here.
         */
        Core::fatalError(
            'Error during session start; please check your PHP and/or '
            . 'webserver log file and configure your PHP '
            . 'installation properly. Also ensure that cookies are enabled '
            . 'in your browser.'
            . '<br /><br />'
            . implode('<br /><br />', $messages)
        );
    }

    /**
     * Set up session
     *
     * @param PhpMyAdmin\Config       $config       Configuration handler
     * @param PhpMyAdmin\ErrorHandler $errorHandler Error handler
     * @return void
     */
    public static function setUp(Config $config, ErrorHandler $errorHandler)
    {
        // verify if PHP supports session, die if it does not
        if (!function_exists('session_name')) {
            Core::warnMissingExtension('session', true);
        } elseif (! empty(ini_get('session.auto_start'))
            && session_name() != 'phpMyAdmin'
            && !empty(session_id())) {
            // Do not delete the existing non empty session, it might be used by
            // other applications; instead just close it.
            if (empty($_SESSION)) {
                // Ignore errors as this might have been destroyed in other
                // request meanwhile
                @session_destroy();
            } elseif (function_exists('session_abort')) {
                // PHP 5.6 and newer
                session_abort();
            } else {
                session_write_close();
            }
        }

        // session cookie settings
        session_set_cookie_params(
            0, $config->getRootPath(),
            '', $config->isHttps(), true
        );

        // cookies are safer (use ini_set() in case this function is disabled)
        ini_set('session.use_cookies', 'true');

        // optionally set session_save_path
        $path = $config->get('SessionSavePath');
        if (!empty($path)) {
            session_save_path($path);
            // We can not do this unconditionally as this would break
            // any more complex setup (eg. cluster), see
            // https://github.com/phpmyadmin/phpmyadmin/issues/8346
            ini_set('session.save_handler', 'files');
        }

        // use cookies only
        ini_set('session.use_only_cookies', '1');
        // strict session mode (do not accept random string as session ID)
        ini_set('session.use_strict_mode', '1');
        // make the session cookie HttpOnly
        ini_set('session.cookie_httponly', '1');
        // do not force transparent session ids
        ini_set('session.use_trans_sid', '0');

        // delete session/cookies when browser is closed
        ini_set('session.cookie_lifetime', '0');

        // warn but don't work with bug
        ini_set('session.bug_compat_42', 'false');
        ini_set('session.bug_compat_warn', 'true');

        // use more secure session ids
        ini_set('session.hash_function', '1');

        // some pages (e.g. stylesheet) may be cached on clients, but not in shared
        // proxy servers
        session_cache_limiter('private');

        $httpCookieName = $config->getCookieName('phpMyAdmin');
        @session_name($httpCookieName);

        // Restore correct sesion ID (it might have been reset by auto started session
        if ($config->issetCookie('phpMyAdmin')) {
            session_id($config->getCookie('phpMyAdmin'));
        }

        // on first start of session we check for errors
        // f.e. session dir cannot be accessed - session file not created
        $orig_error_count = $errorHandler->countErrors(false);

        $session_result = session_start();

        if ($session_result !== true
            || $orig_error_count != $errorHandler->countErrors(false)
        ) {
            setcookie($httpCookieName, '', 1);
            $errors = $errorHandler->sliceErrors($orig_error_count);
            self::sessionFailed($errors);
        }
        unset($orig_error_count, $session_result);

        /**
         * Disable setting of session cookies for further session_start() calls.
         */
        if(session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_cookies', 'true');
        }

        /**
         * Token which is used for authenticating access queries.
         * (we use "space PMA_token space" to prevent overwriting)
         */
        if (empty($_SESSION[' PMA_token '])) {
            self::generateToken();

            /**
             * Check for disk space on session storage by trying to write it.
             *
             * This seems to be most reliable approach to test if sessions are working,
             * otherwise the check would fail with custom session backends.
             */
            $orig_error_count = $errorHandler->countErrors();
            session_write_close();
            if ($errorHandler->countErrors() > $orig_error_count) {
                $errors = $errorHandler->sliceErrors($orig_error_count);
                self::sessionFailed($errors);
            }
            session_start();
            if (empty($_SESSION[' PMA_token '])) {
                Core::fatalError(
                    'Failed to store CSRF token in session! ' .
                    'Probably sessions are not working properly.'
                );
            }
        }
    }
}
