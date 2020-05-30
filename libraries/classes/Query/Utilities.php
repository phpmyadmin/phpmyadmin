<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Url;
use function htmlspecialchars;
use function strpos;
use function strtolower;

/**
 * Some helfull functions for common tasks related to SQL results
 */
class Utilities
{
    /**
     * Get the list of system schemas
     *
     * @return string[] list of system schemas
     */
    public static function getSystemSchemas(): array
    {
        $schemas = [
            'information_schema',
            'performance_schema',
            'mysql',
            'sys',
        ];
        $systemSchemas = [];
        foreach ($schemas as $schema) {
            if (! self::isSystemSchema($schema, true)) {
                continue;
            }

            $systemSchemas[] = $schema;
        }

        return $systemSchemas;
    }

    /**
     * Checks whether given schema is a system schema
     *
     * @param string $schema_name        Name of schema (database) to test
     * @param bool   $testForMysqlSchema Whether 'mysql' schema should
     *                                   be treated the same as IS and DD
     */
    public static function isSystemSchema(
        string $schema_name,
        bool $testForMysqlSchema = false
    ): bool {
        $schema_name = strtolower($schema_name);

        $isMySqlSystemSchema = $schema_name === 'mysql' && $testForMysqlSchema;

        return $schema_name === 'information_schema'
            || $schema_name === 'performance_schema'
            || $isMySqlSystemSchema
            || $schema_name === 'sys';
    }

    /**
     * Formats database error message in a friendly way.
     * This is needed because some errors messages cannot
     * be obtained by mysql_error().
     *
     * @param int    $error_number  Error code
     * @param string $error_message Error message as returned by server
     *
     * @return string HML text with error details
     */
    public static function formatError(int $error_number, string $error_message): string
    {
        $error_message = htmlspecialchars($error_message);

        $error = '#' . ((string) $error_number);
        $separator = ' &mdash; ';

        if ($error_number == 2002) {
            $error .= ' - ' . $error_message;
            $error .= $separator;
            $error .= __(
                'The server is not responding (or the local server\'s socket'
                . ' is not correctly configured).'
            );
        } elseif ($error_number == 2003) {
            $error .= ' - ' . $error_message;
            $error .= $separator . __('The server is not responding.');
        } elseif ($error_number == 1698) {
            $error .= ' - ' . $error_message;
            $error .= $separator . '<a href="' . Url::getFromRoute('/logout') . '" class="disableAjax">';
            $error .= __('Logout and try as another user.') . '</a>';
        } elseif ($error_number == 1005) {
            if (strpos($error_message, 'errno: 13') !== false) {
                $error .= ' - ' . $error_message;
                $error .= $separator
                    . __(
                        'Please check privileges of directory containing database.'
                    );
            } else {
                /**
                 * InnoDB constraints, see
                 * https://dev.mysql.com/doc/refman/5.0/en/
                 * innodb-foreign-key-constraints.html
                 */
                $error .= ' - ' . $error_message .
                    ' (<a href="' .
                    Url::getFromRoute('/server/engines/InnoDB/Status') .
                    '">' . __('Details…') . '</a>)';
            }
        } else {
            $error .= ' - ' . $error_message;
        }

        return $error;
    }
}
