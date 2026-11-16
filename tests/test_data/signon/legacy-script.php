<?php
/**
 * Signon script used by the tests, written for the original contract:
 * one parameter, no identity, extra elements in the returned list.
 */

declare(strict_types=1);

// phpcs:disable Squiz.Functions.GlobalFunction

/** @return array<int,string> */
function get_login_credentials(string $user): array
{
    $calls = $GLOBALS['signon_test_calls'] ?? [];
    $calls = is_array($calls) ? $calls : [];
    $calls[] = $user;
    $GLOBALS['signon_test_calls'] = $calls;

    /** @var array{string, string} $credentials */
    $credentials = $GLOBALS['signon_test_legacy_credentials'] ?? ['', ''];

    return [$credentials[0], $credentials[1], 'some host', 'some request id'];
}
