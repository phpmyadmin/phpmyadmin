<?php
/**
 * Single signon for phpMyAdmin
 *
 * This is just example how to use script based single signon with
 * phpMyAdmin, it is not intended to be perfect code and look, only
 * shows how you can integrate this functionality in your application.
 */

declare(strict_types=1);

// phpcs:disable Squiz.Functions.GlobalFunction

/**
 * This function returns username and password.
 *
 * It can optionally use configured username as parameter,
 * and the number of the server the user is logging in to.
 *
 * @param string $user   User name
 * @param int    $server Number of the server
 *
 * @return array<int|string,string>
 */
function get_login_credentials(string $user, int $server = 1): array
{
    /* Optionally we can use passed username */
    if ($user !== '') {
        return [$user, 'password'];
    }

    /* Here we would retrieve the credentials */
    return ['root', ''];
}
