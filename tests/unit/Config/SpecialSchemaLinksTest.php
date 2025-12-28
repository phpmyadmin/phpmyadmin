<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\SpecialSchemaLinks;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(SpecialSchemaLinks::class)]
final class SpecialSchemaLinksTest extends AbstractTestCase
{
    public function testGetWithCachedValue(): void
    {
        $expected = [
            'link_param' => 'username',
            'link_dependancy_params' => [['param_info' => 'test', 'column_name' => 'Test']],
            'default_page' => './index.php?route=/test_route',
        ];
        $specialSchemaLinks = ['mysql' => ['test_table' => ['test_column' => $expected]]];
        (new ReflectionProperty(SpecialSchemaLinks::class, 'specialSchemaLinks'))->setValue(null, $specialSchemaLinks);

        self::assertSame($expected, SpecialSchemaLinks::get('mysql', 'test_table', 'test_column'));
        self::assertNull(SpecialSchemaLinks::get('mysql', 'test_table', 'unknown_column'));
    }

    /**
     * @param 'mysql'|'information_schema'                                  $database
     * @param list<array{'param_info': string, 'column_name': string}>|null $params
     * @phpstan-param 'sql'|null $defaultTabTable
     * @phpstan-param 'sql'|null $defaultTabDatabase
     */
    #[DataProvider('specialSchemaLinksProvider')]
    public function testGetWithoutCache(
        string $database,
        string $table,
        string $column,
        string $param,
        array|null $params,
        string $page,
        string|null $defaultTabTable = null,
        string|null $defaultTabDatabase = null,
    ): void {
        (new ReflectionProperty(SpecialSchemaLinks::class, 'specialSchemaLinks'))->setValue(null, []);

        $config = new Config();
        $config->set('DefaultTabDatabase', $defaultTabDatabase);
        $config->set('DefaultTabTable', $defaultTabTable);
        Config::$instance = $config;

        $expected = ['link_param' => $param, 'link_dependancy_params' => $params, 'default_page' => $page];
        if ($params === null) {
            unset($expected['link_dependancy_params']);
        }

        self::assertSame($expected, SpecialSchemaLinks::get($database, $table, $column));
    }

    /**
     * @return iterable<int, array{
     *     0: 'mysql'|'information_schema',
     *     1: string,
     *     2: string,
     *     3: string,
     *     4: list<array{'param_info': string, 'column_name': string}>|null,
     *     5: string,
     *     6?: 'sql'|null,
     *     7?: 'sql'|null
     * }>
     */
    public static function specialSchemaLinksProvider(): iterable
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        yield ['mysql', 'columns_priv', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'host']], './index.php?route=/server/privileges&lang=en'];
        yield ['mysql', 'columns_priv', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'Db']], './index.php?route=/sql&lang=en'];
        yield ['mysql', 'columns_priv', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'Db']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['mysql', 'columns_priv', 'column_name', 'field', [['param_info' => 'db', 'column_name' => 'Db'], ['param_info' => 'table', 'column_name' => 'Table_name']], './index.php?route=/table/structure/change&change_column=1&lang=en'];
        yield ['mysql', 'db', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'host']], './index.php?route=/server/privileges&lang=en'];
        yield ['mysql', 'event', 'name', 'item_name', [['param_info' => 'db', 'column_name' => 'db']], './index.php?route=/database/events&edit_item=1&lang=en'];
        yield ['mysql', 'innodb_index_stats', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'database_name']], './index.php?route=/sql&lang=en'];
        yield ['mysql', 'innodb_index_stats', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'database_name']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['mysql', 'innodb_index_stats', 'index_name', 'index', [['param_info' => 'db', 'column_name' => 'database_name'], ['param_info' => 'table', 'column_name' => 'table_name']], './index.php?route=/table/structure&lang=en'];
        yield ['mysql', 'innodb_table_stats', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'database_name']], './index.php?route=/sql&lang=en'];
        yield ['mysql', 'innodb_table_stats', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'database_name']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['mysql', 'proc', 'name', 'item_name', [['param_info' => 'db', 'column_name' => 'db'], ['param_info' => 'item_type', 'column_name' => 'type']], './index.php?route=/database/routines&edit_item=1&lang=en'];
        yield ['mysql', 'proc', 'specific_name', 'item_name', [['param_info' => 'db', 'column_name' => 'db'], ['param_info' => 'item_type', 'column_name' => 'type']], './index.php?route=/database/routines&edit_item=1&lang=en'];
        yield ['mysql', 'proc_priv', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'Host']], './index.php?route=/server/privileges&lang=en'];
        yield ['mysql', 'proc_priv', 'routine_name', 'item_name', [['param_info' => 'db', 'column_name' => 'Db'], ['param_info' => 'item_type', 'column_name' => 'Routine_type']], './index.php?route=/database/routines&edit_item=1&lang=en'];
        yield ['mysql', 'proxies_priv', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'Host']], './index.php?route=/server/privileges&lang=en'];
        yield ['mysql', 'tables_priv', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'Host']], './index.php?route=/server/privileges&lang=en'];
        yield ['mysql', 'tables_priv', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'Db']], './index.php?route=/sql&lang=en'];
        yield ['mysql', 'tables_priv', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'Db']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['mysql', 'user', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'host']], './index.php?route=/server/privileges&lang=en'];
        yield ['information_schema', 'columns', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'columns', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'columns', 'column_name', 'field', [['param_info' => 'db', 'column_name' => 'table_schema'], ['param_info' => 'table', 'column_name' => 'table_name']], './index.php?route=/table/structure/change&change_column=1&lang=en'];
        yield ['information_schema', 'key_column_usage', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'constraint_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'key_column_usage', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'constraint_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'key_column_usage', 'column_name', 'field', [['param_info' => 'db', 'column_name' => 'table_schema'], ['param_info' => 'table', 'column_name' => 'table_name']], './index.php?route=/table/structure/change&change_column=1&lang=en'];
        yield ['information_schema', 'key_column_usage', 'referenced_table_name', 'table', [['param_info' => 'db', 'column_name' => 'referenced_table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'key_column_usage', 'referenced_table_name', 'table', [['param_info' => 'db', 'column_name' => 'referenced_table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'key_column_usage', 'referenced_column_name', 'field', [['param_info' => 'db', 'column_name' => 'referenced_table_schema'], ['param_info' => 'table', 'column_name' => 'referenced_table_name']], './index.php?route=/table/structure/change&change_column=1&lang=en'];
        yield ['information_schema', 'partitions', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'partitions', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'processlist', 'user', 'username', [['param_info' => 'hostname', 'column_name' => 'host']], './index.php?route=/server/privileges&lang=en'];
        yield ['information_schema', 'referential_constraints', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'constraint_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'referential_constraints', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'constraint_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'referential_constraints', 'referenced_table_name', 'table', [['param_info' => 'db', 'column_name' => 'constraint_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'referential_constraints', 'referenced_table_name', 'table', [['param_info' => 'db', 'column_name' => 'constraint_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'routines', 'routine_name', 'item_name', [['param_info' => 'db', 'column_name' => 'routine_schema'], ['param_info' => 'item_type', 'column_name' => 'routine_type']], './index.php?route=/database/routines&lang=en'];
        yield ['information_schema', 'schemata', 'schema_name', 'db', null, './index.php?route=/database/structure&lang=en'];
        yield ['information_schema', 'schemata', 'schema_name', 'db', null, './index.php?route=/database/sql&lang=en', null, 'sql'];
        yield ['information_schema', 'statistics', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'statistics', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'statistics', 'column_name', 'field', [['param_info' => 'db', 'column_name' => 'table_schema'], ['param_info' => 'table', 'column_name' => 'table_name']], './index.php?route=/table/structure/change&change_column=1&lang=en'];
        yield ['information_schema', 'tables', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'tables', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'table_constraints', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'table_constraints', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        yield ['information_schema', 'views', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/sql&lang=en'];
        yield ['information_schema', 'views', 'table_name', 'table', [['param_info' => 'db', 'column_name' => 'table_schema']], './index.php?route=/table/sql&lang=en', 'sql'];
        // phpcs:enable
    }
}
