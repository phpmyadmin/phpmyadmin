<?php
/**
 * Contract for every database extension supported by phpMyAdmin
 *
 * @package PhpMyAdmin-DBI
 */

/**
 * Defines PMA_MYSQL_CLIENT_API if it does not exist based
 * on MySQL client version.
 *
 * @param string $version MySQL version string
 *
 * @return void
 */
function PMA_defineClientAPI($version)
{
    if (!defined('PMA_MYSQL_CLIENT_API')) {
        $client_api = explode('.', $version);
        define(
            'PMA_MYSQL_CLIENT_API',
            (int)sprintf(
                '%d%02d%02d',
                $client_api[0], $client_api[1], intval($client_api[2])
            )
        );
    }
}
