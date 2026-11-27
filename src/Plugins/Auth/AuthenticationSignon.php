<?php
/**
 * SignOn Authentication plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Logging;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Session;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use ReflectionFunction;
use RuntimeException;

use function __;
use function array_merge;
use function defined;
use function file_exists;
use function function_exists;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function preg_replace;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_set_cookie_params;
use function session_start;
use function session_write_close;
use function sprintf;

/**
 * Handles the SignOn authentication method
 */
class AuthenticationSignon extends AuthenticationPlugin
{
    /** Session key remembering the identity the signon script reported for the current session. */
    private const IDENTITY_SESSION_KEY = 'PMA_single_signon_identity';

    /** Session key remembering for which server and database user the second factor was checked. */
    private const TWO_FACTOR_SCOPE_SESSION_KEY = 'PMA_single_signon_2fa_scope';

    /** Identity the signon script reported for the current request, if any. */
    private string|null $identity = null;

    /** Reason the database server refused the login, shown when there is no SignonURL to send the user to. */
    private string $failureMessage = '';

    /** Whether the session had to be reset because it did not belong to the current identity. */
    private bool $sessionWasReset = false;

    /**
     * Gets the credentials, and makes sure a request that was verified
     * against the session of somebody else is not carried out.
     *
     * @throws AuthenticationFailure
     */
    public function authenticate(): Response|null
    {
        if (! $this->readCredentials()) {
            // A session known to belong to the current identity is kept, they are just not allowed on this server.
            if ($this->identity === null) {
                Session::secure();
            }

            return $this->showLoginForm();
        }

        if ($this->sessionWasReset && ! $this->isSafeRequest()) {
            if ($this->responseRenderer->isAjax()) {
                return $this->errorResponse(
                    __('Your session has been restarted, because it was used by somebody else. Please try again.'),
                );
            }

            return $this->redirectResponse(StatusCodeInterface::STATUS_SEE_OTHER, []);
        }

        $this->storeCredentials();
        $this->checkRules();
        Util::clearUserCache();

        if ($this->identity !== null) {
            $this->resetTwoFactorCheckOnAccountChange();
        }

        return null;
    }

    /**
     * Displays authentication form
     */
    public function showLoginForm(): Response
    {
        unset($_SESSION['LAST_SIGNON_URL']);
        $config = Config::getInstance();
        if (empty($config->selectedServer['SignonURL'])) {
            // The signon script decides who may log in, so there is not necessarily a page to send the user to.
            if ($config->selectedServer['SignonScript'] !== '') {
                return $this->showRefusal($config);
            }

            $this->responseRenderer->addHTML($this->template->render('error/generic', [
                'lang' => Current::$lang,
                'error_message' => 'You must set SignonURL!',
            ]));

            return $this->responseRenderer->response();
        }

        return $this->withNoCacheHeaders(
            ResponseFactory::create()->createResponse(StatusCodeInterface::STATUS_FOUND)->withHeader(
                'Location',
                $this->responseRenderer->fixRelativeUrlForRedirect($config->selectedServer['SignonURL']),
            ),
        );
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
     *
     * @throws RuntimeException
     */
    public function readCredentials(): bool
    {
        $config = Config::getInstance();

        /* Script name */
        $scriptName = $config->selectedServer['SignonScript'];

        /* Check if we're using same signon server (the script is asked on every request, so this is not needed) */
        $signonUrl = $config->selectedServer['SignonURL'];
        if ($scriptName === '' && isset($_SESSION['LAST_SIGNON_URL']) && $_SESSION['LAST_SIGNON_URL'] !== $signonUrl) {
            return false;
        }

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
                throw new RuntimeException(sprintf(
                    __('Can not find signon authentication script: %s'),
                    '$cfg[\'Servers\'][$i][\'SignonScript\']',
                ));
            }

            include_once $scriptName;

            $credentials = self::getLoginCredentials($config->selectedServer['user'], Current::$server);
            $this->user = isset($credentials[0]) && is_string($credentials[0]) ? $credentials[0] : '';
            $this->password = isset($credentials[1]) && is_string($credentials[1]) ? $credentials[1] : '';
            $this->bindSessionToIdentity($credentials['identity'] ?? null);
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

        if ($scriptName === '') {
            $_SESSION['LAST_SIGNON_URL'] = $config->selectedServer['SignonURL'];
        }

        return true;
    }

    /**
     * User is not allowed to login to MySQL -> authentication failed
     */
    public function showFailure(AuthenticationFailure $failure): Response
    {
        $this->logFailure($failure);
        $this->failureMessage = $this->getErrorMessage($failure);

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
            $_SESSION['PMA_single_signon_error_message'] = $this->failureMessage;
        }

        return $this->showLoginForm();
    }

    /**
     * Returns URL for login form.
     */
    public function getLoginFormURL(): string
    {
        $signonUrl = Config::getInstance()->selectedServer['SignonURL'];

        return $signonUrl !== '' ? $signonUrl : parent::getLoginFormURL();
    }

    /**
     * Calls the signon script, passing the server number only to scripts that declare a parameter accepting it.
     *
     * @return mixed[]
     */
    private static function getLoginCredentials(string $user, int $server): array
    {
        $parameters = (new ReflectionFunction('get_login_credentials'))->getParameters();
        $type = isset($parameters[1]) ? $parameters[1]->getType() : null;
        // Untyped, int, ?int, mixed or a union containing int.
        $acceptsServer = isset($parameters[1])
            && ($type === null || preg_match('/(^|[|?])(int|mixed)($|\|)/', (string) $type) === 1);

        return $acceptsServer ? get_login_credentials($user, $server) : get_login_credentials($user);
    }

    /**
     * Tells the user that they are not let in, when there is no SignonURL to send them to.
     */
    private function showRefusal(Config $config): Response
    {
        $otherServers = $this->identity !== null ? $this->getOtherServers($config) : [];

        if ($this->failureMessage === '') {
            Logging::logUser(
                $config,
                (string) preg_replace('/[\x00-\x1F\x7F]/', '?', $this->identity ?? ''),
                'signon-refused',
            );

            // Somebody opening the start page without choosing a server is sent to one they can use.
            if (
                $otherServers !== []
                && ! $this->isServerRequested()
                && $this->getRequestedRoute() === '/'
                && ! $this->responseRenderer->isAjax()
            ) {
                return $this->redirectResponse(
                    $this->isSafeRequest() ? StatusCodeInterface::STATUS_FOUND : StatusCodeInterface::STATUS_SEE_OTHER,
                    ['server' => $otherServers[0]['number']],
                );
            }
        }

        if ($this->failureMessage !== '') {
            // The database server refused the credentials: show what went wrong.
            $message = $this->failureMessage;
        } elseif ($this->identity !== null) {
            $message = sprintf(
                __('No login is configured for %s on this server.'),
                '<strong>' . htmlspecialchars($this->identity) . '</strong>',
            );
        } else {
            $message = __('Access denied!');
        }

        if ($otherServers !== []) {
            $links = [];
            foreach ($otherServers as $server) {
                $links[] = '<a href="' . Url::getFromRoute('/', ['server' => $server['number']]) . '">'
                    . htmlspecialchars($server['name']) . '</a>';
            }

            $message .= ' ' . __('You can log in to the following servers:') . ' ' . implode(', ', $links);
        }

        $logoutUrl = $config->selectedServer['LogoutURL'];
        if ($logoutUrl !== '') {
            $message .= ' <a href="' . htmlspecialchars($logoutUrl) . '">'
                . __('Log in as a different user') . '</a>';
        }

        return $this->errorResponse($message);
    }

    /**
     * Asks the signon script which of the other servers using it the user can log in to.
     *
     * Only done for scripts reporting an identity, as only those are written to be asked for any server.
     *
     * @return list<array{number: int, name: string}>
     */
    private function getOtherServers(Config $config): array
    {
        if (! function_exists('get_login_credentials')) {
            return [];
        }

        $servers = [];
        foreach ($config->settings['Servers'] as $number => $server) {
            if (
                $number === Current::$server
                || $server['auth_type'] !== 'signon'
                || $server['host'] === ''
                || $server['SignonScript'] !== $config->selectedServer['SignonScript']
            ) {
                continue;
            }

            $credentials = self::getLoginCredentials($server['user'], $number);
            if (
                ! isset($credentials[0], $credentials['identity'])
                || ! is_string($credentials[0])
                || $credentials[0] === ''
                || $credentials['identity'] !== $this->identity
            ) {
                continue;
            }

            $servers[] = [
                'number' => $number,
                'name' => $server['verbose'] !== '' ? $server['verbose'] : $server['host'],
            ];
        }

        return $servers;
    }

    /**
     * Whether the request names the server to use, as opposed to falling back to the default server.
     */
    private function isServerRequested(): bool
    {
        foreach ([$_GET, $_POST] as $parameters) {
            if (isset($parameters['server']) && is_string($parameters['server']) && $parameters['server'] !== '') {
                return true;
            }
        }

        return false;
    }

    private function getRequestedRoute(): string
    {
        $route = $_GET['route'] ?? $_POST['route'] ?? '/';

        return is_string($route) && $route !== '' ? $route : '/';
    }

    /**
     * Whether the request uses a method that must not change anything.
     */
    private function isSafeRequest(): bool
    {
        return in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true);
    }

    /**
     * The second factor is remembered for the whole session, so it has to be checked again
     * when the same person continues on a server where they log in as another database user.
     */
    private function resetTwoFactorCheckOnAccountChange(): void
    {
        $scope = Current::$server . '/' . $this->user;
        if (($_SESSION[self::TWO_FACTOR_SCOPE_SESSION_KEY] ?? null) === $scope) {
            return;
        }

        unset($_SESSION['two_factor_check']);
        $_SESSION[self::TWO_FACTOR_SCOPE_SESSION_KEY] = $scope;
    }

    /** @param array<string, int|string|bool> $params */
    private function redirectResponse(int $statusCode, array $params): Response
    {
        return $this->withNoCacheHeaders(
            ResponseFactory::create()->createResponse($statusCode)->withHeader(
                'Location',
                $this->responseRenderer->fixRelativeUrlForRedirect(
                    './index.php?route=/' . Url::getCommonRaw($params, '&'),
                ),
            ),
        );
    }

    private function errorResponse(string $message): Response
    {
        if ($this->responseRenderer->isAjax()) {
            $this->responseRenderer->setRequestStatus(false);
            $this->responseRenderer->addJSON('message', Message::rawError($message));
            // Reload the page, so the reason is shown where the user can read it.
            $this->responseRenderer->addJSON('reload_flag', '1');

            return $this->responseRenderer->response();
        }

        return $this->withNoCacheHeaders(
            ResponseFactory::create()->createResponse(StatusCodeInterface::STATUS_FORBIDDEN)->write(
                $this->template->render('error/generic', ['lang' => Current::$lang, 'error_message' => $message]),
            ),
        );
    }

    /**
     * The answer depends on who is asking, so it must never be reused from a cache.
     */
    private function withNoCacheHeaders(Response $response): Response
    {
        foreach (Core::getNoCacheHeaders(new Clock()) as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Makes sure the session is only used by the identity it was started for.
     *
     * The signon script may return an identifier of the person it authenticated under the key "identity".
     * Without it the script is assumed to always authenticate the same person for a given browser session.
     */
    private function bindSessionToIdentity(mixed $identity): void
    {
        if (! is_string($identity) || $identity === '') {
            return;
        }

        $this->identity = $identity;
        if (($_SESSION[self::IDENTITY_SESSION_KEY] ?? null) === $identity) {
            return;
        }

        Session::secure();
        $_SESSION[self::IDENTITY_SESSION_KEY] = $identity;
        $this->sessionWasReset = true;
    }
}
