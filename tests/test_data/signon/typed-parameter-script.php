<?php
/**
 * Signon script used by the tests whose own optional second parameter can not take a server number.
 */

declare(strict_types=1);

// phpcs:disable Squiz.Functions.GlobalFunction

/** @return array<int,string> */
function get_login_credentials(string $user, string $realm = 'default'): array
{
    return ['realm-' . $realm, 'secret'];
}
