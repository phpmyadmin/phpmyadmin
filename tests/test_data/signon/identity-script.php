<?php
/**
 * Signon script used by the tests: identity from the X-Test-Identity header,
 * credentials from $GLOBALS['signon_test_users'][$server][$identity].
 */

declare(strict_types=1);

// phpcs:disable Squiz.Functions.GlobalFunction

/** @return array<int|string,string> */
function get_login_credentials(string $user, int $server = 1): array
{
    $calls = $GLOBALS['signon_test_calls'] ?? [];
    $calls = is_array($calls) ? $calls : [];
    $calls[] = $server;
    $GLOBALS['signon_test_calls'] = $calls;

    $identity = $_SERVER['HTTP_X_TEST_IDENTITY'] ?? null;
    if (! is_string($identity) || $identity === '') {
        return ['', ''];
    }

    /** @var array<int, array<string, array{string, string}>> $users */
    $users = $GLOBALS['signon_test_users'] ?? [];
    $credentials = $users[$server][$identity] ?? ['', ''];

    return [$credentials[0], $credentials[1], 'identity' => $identity];
}
