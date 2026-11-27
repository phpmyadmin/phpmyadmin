<?php
/**
 * Single signon for phpMyAdmin behind an authenticating reverse proxy
 *
 * This example shows how to log users in automatically when phpMyAdmin runs
 * behind a reverse proxy that authenticates them (for example using OAuth 2.0
 * or OpenID Connect) and passes their identity in a request header.
 *
 * The identity is mapped to the MySQL user and password to log in with:
 *
 *     $cfg['Servers'][$i]['auth_type'] = 'signon';
 *     $cfg['Servers'][$i]['SignonScript'] = '/path/to/signon-proxy-header.php';
 *     $cfg['Servers'][$i]['LogoutURL'] = 'https://pma.example.com/oauth2/sign_out';
 *
 * Anybody able to reach phpMyAdmin without passing through the proxy could
 * send the header themselves, so it is only accepted from the addresses
 * listed in SIGNON_TRUSTED_PROXIES.
 */

declare(strict_types=1);

// phpcs:disable Squiz.Functions.GlobalFunction,PSR1.Files.SideEffects

/**
 * Name of the request header the proxy passes the identity of the user in.
 */
const SIGNON_HEADER = 'X-Forwarded-Email';

/**
 * Addresses of the proxies allowed to set the header: IPv4 or IPv6, either a
 * single address or CIDR notation (list IPv4 proxies in IPv4 notation).
 * Keep this list as narrow as possible: never list a whole container network
 * or its gateway, as every other container could then pretend to be anybody.
 */
const SIGNON_TRUSTED_PROXIES = ['127.0.0.1', '::1'];

/**
 * MySQL credentials of the users, per server number ($i in config.inc.php).
 * Identities are compared case-insensitively (ASCII letters only).
 */
const SIGNON_USERS = [
    1 => [
        'alice@example.com' => ['alice', 'password of alice'],
        'bob@example.com' => ['reporting', 'password of reporting'],
    ],
];

/**
 * This function returns username and password, and the identity they belong to.
 *
 * Returning the identity makes phpMyAdmin start a new session whenever
 * somebody else continues with the same browser session. phpMyAdmin may also
 * call this function for other servers, to offer the user the servers they
 * can log in to, so it must not have side effects.
 *
 * @param string $user   User name from the configuration, not used
 * @param int    $server Number of the server the user is logging in to
 *
 * @return array<int|string,string>
 *
 * @psalm-suppress UnusedVariable
 */
function get_login_credentials(string $user, int $server = 1): array
{
    $identity = signon_get_identity();
    if ($identity === null) {
        return ['', ''];
    }

    foreach (SIGNON_USERS[$server] ?? [] as $knownIdentity => $credentials) {
        // PHP turns numeric array keys into integers.
        $knownIdentity = strval($knownIdentity);
        if (strcasecmp($knownIdentity, $identity) === 0) {
            return [$credentials[0], $credentials[1], 'identity' => strtolower($identity)];
        }
    }

    // Known person without a login on this server: no credentials, but the session stays theirs.
    return ['', '', 'identity' => strtolower($identity)];
}

/**
 * Returns the identity passed by the proxy, or null if the request does not
 * come from a trusted proxy or carries no usable identity.
 */
function signon_get_identity(): string|null
{
    $key = signon_header_to_server_key(SIGNON_HEADER);

    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    if (! is_string($remoteAddress) || ! signon_is_trusted_proxy($remoteAddress, SIGNON_TRUSTED_PROXIES)) {
        if (isset($_SERVER[$key])) {
            signon_log_once(
                'ignoring the ' . SIGNON_HEADER . ' header from untrusted address ' . var_export($remoteAddress, true),
            );
        }

        return null;
    }

    // Depending on the web server, PHP makes a header named "X_Forwarded_Email" or "X.Forwarded.Email"
    // available under the same name as "X-Forwarded-Email", while the proxy only replaces the header it
    // knows. Requests where the header is ambiguous are refused. This only works where PHP sees the
    // original header names (Apache module, built-in web server); with PHP-FPM or CGI the web server in
    // front of PHP has to drop such headers (nginx and Apache do by default).
    if (function_exists('getallheaders')) {
        /** @var array<string, string> $headers */
        $headers = getallheaders();
        if (signon_header_is_ambiguous(array_map('strval', array_keys($headers)), SIGNON_HEADER)) {
            signon_log_once('refusing a request with more than one header named like ' . SIGNON_HEADER);

            return null;
        }
    }

    $value = $_SERVER[$key] ?? null;
    if (! is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ($value === '' || ! mb_check_encoding($value, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
        return null;
    }

    return $value;
}

/**
 * Returns the name under which PHP makes a request header available in $_SERVER.
 */
function signon_header_to_server_key(string $header): string
{
    return 'HTTP_' . strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '_', $header));
}

/**
 * Whether the request carries the header more than once, or carries another
 * header that PHP makes available under the same name.
 *
 * @param string[] $headerNames names of all request headers
 */
function signon_header_is_ambiguous(array $headerNames, string $header): bool
{
    $key = signon_header_to_server_key($header);
    $matches = 0;
    foreach ($headerNames as $name) {
        if (signon_header_to_server_key($name) !== $key) {
            continue;
        }

        if (strcasecmp($name, $header) !== 0) {
            return true;
        }

        $matches++;
    }

    return $matches > 1;
}

/**
 * Whether the address belongs to one of the trusted proxies.
 * Entries which are not a valid address or network never match.
 *
 * @param string[] $trustedProxies
 */
function signon_is_trusted_proxy(string $remoteAddress, array $trustedProxies): bool
{
    $remote = signon_parse_address($remoteAddress);
    if ($remote === null) {
        return false;
    }

    foreach ($trustedProxies as $entry) {
        $parts = explode('/', $entry, 2);
        $network = signon_parse_address($parts[0]);
        if ($network === null || strlen($network) !== strlen($remote)) {
            continue;
        }

        $prefix = strlen($network) * 8;
        if (isset($parts[1])) {
            if (! ctype_digit($parts[1]) || strlen($parts[1]) > 3) {
                continue;
            }

            $prefix = (int) $parts[1];
            // An IPv4 network written as IPv4-mapped IPv6 network, e.g. ::ffff:10.0.0.0/104.
            if (strlen($network) === 4 && str_contains($parts[0], ':')) {
                $prefix -= 96;
            }

            if ($prefix < 0 || $prefix > strlen($network) * 8) {
                continue;
            }
        }

        $bytes = intdiv($prefix, 8);
        if (substr($network, 0, $bytes) !== substr($remote, 0, $bytes)) {
            continue;
        }

        $remainder = $prefix % 8;
        if ($remainder === 0) {
            return true;
        }

        $mask = 0xFF << 8 - $remainder & 0xFF;
        if ((ord($network[$bytes]) & $mask) === (ord($remote[$bytes]) & $mask)) {
            return true;
        }
    }

    return false;
}

/**
 * Returns the packed form of an IP address, or null if it is not valid.
 * IPv4-mapped IPv6 addresses (::ffff:a.b.c.d) are treated as IPv4.
 */
function signon_parse_address(string $address): string|null
{
    if (filter_var($address, FILTER_VALIDATE_IP) === false) {
        return null;
    }

    $packed = inet_pton($address);
    if ($packed === false) {
        return null;
    }

    if (strlen($packed) === 16 && str_starts_with($packed, str_repeat("\0", 10) . "\xFF\xFF")) {
        return substr($packed, 12);
    }

    return $packed;
}

/**
 * Writes a message to the PHP error log, at most once per request,
 * so the administrator can see why logins are refused.
 */
function signon_log_once(string $message): void
{
    /** @var array<string, true> $logged */
    static $logged = [];
    if (isset($logged[$message])) {
        return;
    }

    $logged[$message] = true;
    error_log('phpMyAdmin signon: ' . $message);
}
