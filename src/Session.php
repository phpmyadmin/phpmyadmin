<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Exceptions\SessionHandlerException;

use function htmlspecialchars;
use function implode;
use function ini_get;
use function ini_set;
use function preg_replace;
use function session_abort;
use function session_cache_limiter;
use function session_destroy;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_save_path;
use function session_set_cookie_params;
use function session_start;
use function session_status;
use function session_unset;
use function session_write_close;
use function setcookie;

use const PHP_SESSION_ACTIVE;

class Session
{
    /**
     * Generates PMA_token session variable.
     *
     * @throws SessionHandlerException
     */
    private static function generateToken(): void
    {
        $_SESSION[' PMA_token '] = Util::generateRandom(16, true);
        $_SESSION[' HMAC_secret '] = Util::generateRandom(16);

        /**
         * Check if token is properly generated (the generation can fail, for example
         * due to missing /dev/random for openssl).
         */
        if (! empty($_SESSION[' PMA_token '])) {
            return;
        }

        throw new SessionHandlerException('Failed to generate random CSRF token!');
    }

    /**
     * tries to secure session from hijacking and fixation
     * should be called before login and after successful login
     * (only required if sensitive information stored in session)
     *
     * @throws SessionHandlerException
     */
    public static function secure(): void
    {
        // prevent session fixation and XSS
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // continue with empty session
        session_unset();
        self::generateToken();
    }

    /**
     * Session failed function
     *
     * @param mixed[] $errors PhpMyAdmin\ErrorHandler array
     *
     * @throws SessionHandlerException
     */
    private static function sessionFailed(array $errors): void
    {
        $messages = [];
        foreach ($errors as $error) {
            /**
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
                htmlspecialchars($error->getMessage()),
            );
        }

        // Session initialization is done before selecting language, so we can not use translations here.
        $errorMessage = 'Error during session start; please check your PHP and/or '
            . 'webserver log file and configure your PHP '
            . 'installation properly. Also ensure that cookies are enabled '
            . 'in your browser.'
            . '<br><br>'
            . implode('<br><br>', $messages);

        throw new SessionHandlerException($errorMessage);
    }

    /** @throws SessionHandlerException */
    public static function setUp(Config $config, ErrorHandler $errorHandler): void
    {
        if (! empty(ini_get('session.auto_start')) && session_name() !== 'phpMyAdmin' && ! empty(session_id())) {
            // Do not delete the existing non empty session, it might be used by
            // other applications; instead just close it.
            if ($_SESSION === []) {
                // Ignore errors as this might have been destroyed in other
                // request meanwhile
                @session_destroy();
            } else {
                // do not use session_write_close, see issue #13392
                session_abort();
            }
        }

        /** @psalm-var 'Lax'|'Strict'|'None' $cookieSameSite */
        $cookieSameSite = $config->get('CookieSameSite') ?? 'Strict';
        $cookiePath = $config->getRootPath();

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'domain' => '',
            'secure' => $config->isHttps(),
            'httponly' => true,
            'samesite' => $cookieSameSite,
        ]);

        // cookies are safer (use ini_set() in case this function is disabled)
        ini_set('session.use_cookies', 'true');

        // optionally set session_save_path
        $path = $config->get('SessionSavePath');
        if (! empty($path)) {
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
        // add SameSite to the session cookie
        ini_set('session.cookie_samesite', $cookieSameSite);

        // do not force transparent session ids
        ini_set('session.use_trans_sid', '0');

        // delete session/cookies when browser is closed
        ini_set('session.cookie_lifetime', '0');

        // some pages (e.g. stylesheet) may be cached on clients, but not in shared
        // proxy servers
        session_cache_limiter('private');

        $httpCookieName = $config->getCookieName('phpMyAdmin');
        @session_name($httpCookieName);

        // Restore correct session ID (it might have been reset by auto started session
        if ($config->issetCookie('phpMyAdmin')) {
            session_id($config->getCookie('phpMyAdmin'));
        }

        // on first start of session we check for errors
        // f.e. session dir cannot be accessed - session file not created
        $origErrorCount = $errorHandler->countErrors(false);

        $sessionResult = session_start();

        if (! $sessionResult || $origErrorCount !== $errorHandler->countErrors(false)) {
            setcookie($httpCookieName, '', 1);
            $errors = $errorHandler->sliceErrors($origErrorCount);
            self::sessionFailed($errors);
        }

        unset($origErrorCount, $sessionResult);

        /**
         * Disable setting of session cookies for further session_start() calls.
         */
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_cookies', 'true');
        }

        /**
         * Token which is used for authenticating access queries.
         * (we use "space PMA_token space" to prevent overwriting)
         */
        if (! empty($_SESSION[' PMA_token '])) {
            return;
        }

        self::generateToken();

        /**
         * Check for disk space on session storage by trying to write it.
         *
         * This seems to be most reliable approach to test if sessions are working,
         * otherwise the check would fail with custom session backends.
         */
        $origErrorCount = $errorHandler->countErrors();
        session_write_close();
        if ($errorHandler->countErrors() > $origErrorCount) {
            $errors = $errorHandler->sliceErrors($origErrorCount);
            self::sessionFailed($errors);
        }

        session_start();
        if (! empty($_SESSION[' PMA_token '])) {
            return;
        }

        throw new SessionHandlerException(
            'Failed to store CSRF token in session! Probably sessions are not working properly.',
        );
    }
}
