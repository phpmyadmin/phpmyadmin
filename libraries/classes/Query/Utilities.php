<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

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
}
