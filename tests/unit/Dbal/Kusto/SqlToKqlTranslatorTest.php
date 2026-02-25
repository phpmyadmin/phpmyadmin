<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal\Kusto;

use PhpMyAdmin\Dbal\Kusto\SqlToKqlTranslator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlToKqlTranslator::class)]
class SqlToKqlTranslatorTest extends TestCase
{
    public function testShowDatabases(): void
    {
        self::assertSame('.show databases', SqlToKqlTranslator::translate('SHOW DATABASES'));
        self::assertSame('.show databases', SqlToKqlTranslator::translate('SHOW SCHEMAS'));
        self::assertSame('.show databases', SqlToKqlTranslator::translate('SHOW DATABASES;'));
    }

    public function testShowTables(): void
    {
        self::assertSame('.show tables', SqlToKqlTranslator::translate('SHOW TABLES'));
        self::assertSame(
            '.show database mydb tables',
            SqlToKqlTranslator::translate('SHOW TABLES FROM `mydb`'),
        );
    }

    public function testShowFullTables(): void
    {
        self::assertSame('.show tables', SqlToKqlTranslator::translate('SHOW FULL TABLES'));
    }

    public function testShowCreateTable(): void
    {
        self::assertSame(
            '.show table users schema as json',
            SqlToKqlTranslator::translate('SHOW CREATE TABLE users'),
        );
    }

    public function testShowColumns(): void
    {
        self::assertSame(
            '.show table users schema as cslschema',
            SqlToKqlTranslator::translate('SHOW COLUMNS FROM users'),
        );
        self::assertSame(
            '.show table users schema as cslschema',
            SqlToKqlTranslator::translate('SHOW FULL COLUMNS FROM `users`'),
        );
    }

    public function testDescribe(): void
    {
        self::assertSame(
            '.show table users schema as cslschema',
            SqlToKqlTranslator::translate('DESCRIBE users'),
        );
    }

    public function testShowVariables(): void
    {
        self::assertSame('.show version', SqlToKqlTranslator::translate('SHOW VARIABLES'));
        self::assertSame('.show version', SqlToKqlTranslator::translate('SHOW SESSION VARIABLES LIKE \'%version%\''));
    }

    public function testShowProcesslist(): void
    {
        self::assertSame('.show queries', SqlToKqlTranslator::translate('SHOW PROCESSLIST'));
        self::assertSame('.show queries', SqlToKqlTranslator::translate('SHOW FULL PROCESSLIST'));
    }

    public function testSelectCount(): void
    {
        self::assertSame(
            'users | count',
            SqlToKqlTranslator::translate('SELECT COUNT(*) FROM users'),
        );
    }

    public function testSelectAllWithLimit(): void
    {
        $result = SqlToKqlTranslator::translate('SELECT * FROM users LIMIT 50');
        self::assertNotNull($result);
        self::assertStringContainsString('users', $result);
        self::assertStringContainsString('take 50', $result);
    }

    public function testSelectAllWithLimitAndOffset(): void
    {
        $result = SqlToKqlTranslator::translate('SELECT * FROM users LIMIT 10, 50');
        self::assertNotNull($result);
        self::assertStringContainsString('users', $result);
        self::assertStringContainsString('take 50', $result);
    }

    public function testSelectAllDefault(): void
    {
        $result = SqlToKqlTranslator::translate('SELECT * FROM logs');
        self::assertNotNull($result);
        self::assertStringContainsString('logs', $result);
        self::assertStringContainsString('take 1000', $result);
    }

    public function testDropTable(): void
    {
        self::assertSame(
            '.drop table old_data ifexists',
            SqlToKqlTranslator::translate('DROP TABLE old_data'),
        );
        self::assertSame(
            '.drop table old_data ifexists',
            SqlToKqlTranslator::translate('DROP TABLE IF EXISTS old_data'),
        );
    }

    public function testInformationSchemaSchemata(): void
    {
        $result = SqlToKqlTranslator::translate(
            "SELECT * FROM INFORMATION_SCHEMA.SCHEMATA",
        );
        self::assertSame('.show databases', $result);
    }

    public function testInformationSchemaTables(): void
    {
        $result = SqlToKqlTranslator::translate(
            "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'mydb'",
        );
        self::assertSame('.show database mydb tables', $result);
    }

    public function testInformationSchemaColumns(): void
    {
        $result = SqlToKqlTranslator::translate(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users'",
        );
        self::assertSame('.show table users schema as cslschema', $result);
    }

    public function testKqlPassthrough(): void
    {
        // Management commands pass through unchanged
        self::assertSame('.show databases', SqlToKqlTranslator::translate('.show databases'));

        // Native KQL queries pass through
        self::assertSame(
            'StormEvents | take 10',
            SqlToKqlTranslator::translate('StormEvents | take 10'),
        );
    }

    public function testMysqlSpecificReturnsNull(): void
    {
        // MySQL-specific commands that should be ignored
        self::assertNull(SqlToKqlTranslator::translate('SET NAMES utf8'));
        self::assertNull(SqlToKqlTranslator::translate('SHOW GRANTS'));
        self::assertNull(SqlToKqlTranslator::translate('SHOW ENGINES'));
        self::assertNull(SqlToKqlTranslator::translate('FLUSH PRIVILEGES'));
        self::assertNull(SqlToKqlTranslator::translate('SELECT 1'));
    }

    public function testShowFunctionStatus(): void
    {
        self::assertSame('.show functions', SqlToKqlTranslator::translate('SHOW FUNCTION STATUS'));
    }

    public function testSelectWithWhere(): void
    {
        $result = SqlToKqlTranslator::translate(
            "SELECT name, age FROM users WHERE age > 18 LIMIT 100",
        );
        self::assertNotNull($result);
        self::assertStringContainsString('where', $result);
        self::assertStringContainsString('take 100', $result);
        self::assertStringContainsString('project', $result);
    }

    public function testSelectWithOrderBy(): void
    {
        $result = SqlToKqlTranslator::translate(
            "SELECT * FROM events ORDER BY timestamp DESC LIMIT 50",
        );
        self::assertNotNull($result);
        self::assertStringContainsString('sort by', $result);
        self::assertStringContainsString('desc', $result);
        self::assertStringContainsString('take 50', $result);
    }
}
